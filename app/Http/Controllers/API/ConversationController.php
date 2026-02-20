<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Order;
use App\Models\Message;
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

            $conversations = Conversation::with([
                    'customer:id,name,email,avatar',
                    'merchant:id,name,logo,user_id,shop_name,phone',
                    'product:id,name',
                    'order:id,order_number,status,total_price,customer_name,customer_phone,shipping_address,shipping_city,payment_method',
                    'latestMessage'
                ])
                ->where(function($query) use ($user) {
                    $query->where('customer_id', $user->id)
                          ->orWhereHas('merchant', function($q) use ($user) {
                              $q->where('user_id', $user->id);
                          });
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
                'message' => 'Erreur lors de la récupération des conversations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Afficher une conversation spécifique
    public function show(Conversation $conversation)
    {
        try {
            $user = auth()->user();
            
            // Vérifier l'accès
            $hasAccess = $conversation->customer_id === $user->id || 
                        ($conversation->merchant && $conversation->merchant->user_id === $user->id);
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

             $conversation->load([
            'customer:id,name,email,avatar',
            'merchant:id,name,logo,user_id,shop_name,phone',
            'product:id,name',
            'order:id,order_number,status,total_price,customer_name,customer_phone,shipping_address,shipping_city,payment_method',
            'order.items', //
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
                'message' => 'Erreur lors de l\'affichage de la conversation'
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
            
            if ($request->product_id) {
                $product = Product::findOrFail($request->product_id);
                $merchantId = $product->merchant_id;
            } elseif ($request->order_id) {
                $order = Order::with('items.product')->findOrFail($request->order_id);
                $merchantId = $order->merchant_id;
                
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
                    'merchant_id' => $merchantId,
                    'last_message_at' => now(),
                ]);
                
                Log::info('✅ Conversation créée', [
                    'conversation_id' => $conversation->id,
                    'product_id' => $productId,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $conversation->load([
                    'customer:id,name,email,avatar',
                    'merchant:id,name,logo,shop_name,phone',
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
                'message' => 'Erreur lors de la création de la conversation'
            ], 500);
        }
    }
}