<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\Wishlist;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Profil du client
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            
            // Stats du client
            $stats = [
                'total_orders' => Order::where('user_id', $user->id)->count(),
                'pending_orders' => Order::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped'])
                    ->count(),
                'completed_orders' => Order::where('user_id', $user->id)
                    ->where('status', 'delivered')
                    ->count(),
                'total_spent' => Order::where('user_id', $user->id)
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->sum('total_price'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'created_at' => $user->created_at,
                    ],
                    'stats' => $stats,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur profil client', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil'
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur mise à jour profil', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe changé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur changement mot de passe', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement'
            ], 500);
        }
    }

    /**
     * Liste des commandes du client
     */
    public function orders(Request $request)
    {
        try {
            $user = $request->user();

            $query = Order::with(['items.product.images', 'merchant'])
                ->where('user_id', $user->id);

            // Filtrer par statut
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur commandes client', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Détails d'une commande
     */
    public function orderDetails($id)
    {
        try {
            $user = auth()->user();

            $order = Order::with([
                'items.product.images',
                'merchant:id,name,shop_name,email,phone'
            ])
            ->where('user_id', $user->id)
            ->findOrFail($id);

            // Conversation associée
            $conversation = Conversation::with('messages')
                ->where('order_id', $order->id)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'conversation' => $conversation,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur détails commande', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }
    }

    /**
     * Annuler une commande
     */
    public function cancelOrder(Request $request, $id)
    {
        try {
            $user = $request->user();

            $order = Order::where('user_id', $user->id)->findOrFail($id);

            // Vérifier si la commande peut être annulée
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être annulée'
                ], 422);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Restaurer le stock
            foreach ($order->items as $item) {
                $product = $item->product;
                $product->increment('stock_quantity', $item->quantity);
            }

            Log::info('✅ Commande annulée par le client', [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur annulation commande', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    /**
     * Conversations du client
     */
    public function conversations()
    {
        try {
            $user = auth()->user();

            $conversations = Conversation::with(['order', 'merchant'])
                ->whereHas('order', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->withCount(['messages as unread_messages' => function($query) {
                    $query->where('is_read', false)
                          ->where('sender_type', 'merchant');
                }])
                ->orderBy('last_message_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur conversations client', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Messages d'une conversation
     */
    public function conversationMessages($orderId)
    {
        try {
            $user = auth()->user();

            // Vérifier que la commande appartient au client
            $order = Order::where('user_id', $user->id)->findOrFail($orderId);

            $conversation = Conversation::with('messages.sender')
                ->where('order_id', $orderId)
                ->firstOrFail();

            // Marquer les messages du merchant comme lus
            $conversation->messages()
                ->where('sender_type', 'merchant')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur messages conversation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable'
            ], 404);
        }
    }

    /**
     * Envoyer un message au merchant
     */
    public function sendMessage(Request $request, $orderId)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            // Vérifier que la commande appartient au client
            $order = Order::where('user_id', $user->id)->findOrFail($orderId);

            $conversation = Conversation::firstOrCreate(
                ['order_id' => $orderId],
                ['merchant_id' => $order->merchant_id]
            );

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'sender_type' => 'customer',
                'content' => $validated['content'],
                'is_read' => false,
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé',
                'data' => $message->load('sender')
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi message', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi'
            ], 500);
        }
    }

    /**
     * Liste de souhaits (wishlist)
     */
    public function wishlist()
    {
        try {
            $user = auth()->user();

            $wishlist = DB::table('wishlists')
                ->join('products', 'wishlists.product_id', '=', 'products.id')
                ->where('wishlists.user_id', $user->id)
                ->select('products.*', 'wishlists.created_at as added_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $wishlist
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Ajouter à la wishlist
     */
    public function addToWishlist($productId)
    {
        try {
            $user = auth()->user();

            // Vérifier si déjà dans la wishlist
            $exists = DB::table('wishlists')
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit déjà dans les favoris'
                ], 422);
            }

            DB::table('wishlists')->insert([
                'user_id' => $user->id,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ajouté aux favoris'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout'
            ], 500);
        }
    }

    /**
     * Retirer de la wishlist
     */
    public function removeFromWishlist($productId)
    {
        try {
            $user = auth()->user();

            DB::table('wishlists')
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Retiré des favoris'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}