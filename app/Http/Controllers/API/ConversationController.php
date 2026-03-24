<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Order;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    // Récupérer toutes les conversations de l'utilisateur
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié'
                ], 401);
            }

            Log::info('🔍 Récupération des conversations', [
                'user_id' => $user->id,
            ]);

            // ✅ Récupérer les conversations où l'utilisateur est client OU marchand
            $conversations = Conversation::with([
                'customer:id,name,email,avatar',
                'merchant:id,name,shop_name,email,avatar', // ✅ merchant est maintenant User
                'product:id,name',
                'order:id,order_number,status,total_price,customer_name,customer_phone,shipping_address,shipping_city,payment_method',
                'latestMessage'
            ])
            ->where(function($query) use ($user) {
                $query->where('customer_id', $user->id)
                      ->orWhere('merchant_id', $user->id); // ✅ directement l'ID de l'utilisateur
            })
            ->orderBy('last_message_at', 'desc')
            ->get();

            Log::info('✅ Conversations trouvées', [
                'count' => $conversations->count()
            ]);

            // Ajouter le nombre de messages non lus
            foreach ($conversations as $conversation) {
                $conversation->unread_count = $conversation->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => $conversations
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération conversations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    // Afficher une conversation spécifique
    public function show(Conversation $conversation)
    {
        try {
            $user = auth()->user();
            
            // ✅ Vérifier l'accès (customer_id ou merchant_id = user_id)
            $hasAccess = $conversation->customer_id === $user->id || 
                         $conversation->merchant_id === $user->id;
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $conversation->load([
                'customer:id,name,email,avatar',
                'merchant:id,name,shop_name,email,avatar,phone', // ✅ merchant est User
                'product:id,name',
                'order:id,order_number,status,total_price,customer_name,customer_phone,shipping_address,shipping_city,payment_method',
                'order.items',
                'order.items.product.images',
                'messages'
            ]);

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur affichage conversation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'affichage de la conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    // Créer une nouvelle conversation
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required_without:order_id|exists:products,id',
                'order_id' => 'required_without:product_id|exists:orders,id',
            ]);

            $user = $request->user();
            
            $productId = $request->product_id;
            $merchantId = null;
            
            if ($request->product_id) {
                $product = Product::findOrFail($request->product_id);
                $merchantId = $product->merchant_id; // ✅ déjà user_id
            } elseif ($request->order_id) {
                $order = Order::with('items.product')->findOrFail($request->order_id);
                $merchantId = $order->merchant_id; // ✅ déjà user_id
                
                if (!$productId && $order->items->isNotEmpty()) {
                    $productId = $order->items->first()->product_id;
                }
            } else {
                return response()->json(['message' => 'product_id ou order_id requis'], 400);
            }

            // Vérifier si une conversation existe déjà
            $conversation = Conversation::where('customer_id', $user->id)
                ->where('merchant_id', $merchantId)
                ->when($productId, function($query, $productId) {
                    return $query->where('product_id', $productId);
                })
                ->when($request->order_id, function($query, $orderId) {
                    return $query->where('order_id', $orderId);
                })
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'product_id' => $productId,
                    'order_id' => $request->order_id,
                    'customer_id' => $user->id,
                    'merchant_id' => $merchantId, // ✅ user_id
                    'last_message_at' => now(),
                ]);
                
                Log::info('✅ Conversation créée', [
                    'conversation_id' => $conversation->id,
                    'product_id' => $productId,
                    'merchant_id' => $merchantId
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $conversation->load([
                    'customer:id,name,email,avatar',
                    'merchant:id,name,shop_name,email,avatar,phone',
                    'product:id,name',
                    'order:id,order_number'
                ])
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création conversation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    // Migrer les conversations guest
    public function migrateGuest(Request $request)
    {
        try {
            $user = $request->user();
            $conversationIds = $request->input('conversation_ids', []);

            if (empty($conversationIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucune conversation à migrer'
                ]);
            }

            $updated = Conversation::whereIn('id', $conversationIds)
                ->whereNull('customer_id')
                ->update(['customer_id' => $user->id]);

            Log::info('✅ Conversations migrées', [
                'count' => $updated,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} conversation(s) migrée(s) avec succès"
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur migration conversations guest', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la migration'
            ], 500);
        }
    }
}