<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class MerchantDashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        try {
            Log::info('🔍 === DÉBUT DEBUG DASHBOARD ===');
            
            $authenticatedUser = $request->user();
            
            if (!$authenticatedUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Utilisateur non authentifié',
                ], 401);
            }

            Log::info('👤 Utilisateur authentifié:', [
                'id' => $authenticatedUser->id,
                'type' => get_class($authenticatedUser),
                'email' => $authenticatedUser->email
            ]);

            // Vérifier si c'est un Merchant ou un User
            if ($authenticatedUser instanceof \App\Models\Merchant) {
                $merchant = $authenticatedUser;
            } else {
                // Si c'est un User, trouver le Merchant associé
                $merchant = Merchant::where('user_id', $authenticatedUser->id)->first();
            }

            if (!$merchant) {
                Log::error('❌ Aucun merchant trouvé pour l\'utilisateur:', ['user_id' => $authenticatedUser->id]);
                return response()->json([
                    'success' => false,
                    'error' => 'Aucun profil marchand trouvé',
                ], 404);
            }

            Log::info('🏪 Merchant trouvé:', [
                'merchant_id' => $merchant->id,
                'merchant_email' => $merchant->email
            ]);

            // ✅ Maintenant on peut utiliser Order
            $totalProducts = Product::where('merchant_id', $merchant->id)->count();
            $totalOrders = Order::where('merchant_id', $merchant->id)->count();
            $totalRevenue = Order::where('merchant_id', $merchant->id)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            // Commandes récentes
            $recentOrders = Order::where('merchant_id', $merchant->id)
                ->with('items')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer' => $order->customer_name,
                        'product' => $order->items->first()->product_name ?? 'N/A',
                        'amount' => $order->total_price,
                        'status' => $this->getStatusBadge($order->status),
                    ];
                });

            // Produits
            $products = Product::where('merchant_id', $merchant->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $product->stock ?? 0
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Données du tableau de bord récupérées avec succès ✅',
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'email' => $merchant->email,
                    'phone' => $merchant->phone ?? '',
                    'shop_name' => $merchant->shop_name ?? '',
                    'shop_address' => $merchant->shop_address ?? '',
                    'followers_count' => $merchant->followers_count,
  'is_followed' => auth()->check()
      ? $merchant->isFollowedBy(auth()->user())
      : false,
                    'country' => $merchant->country ?? '',
                    'category' => $merchant->category ?? '',
                    'payment_method' => $merchant->payment_method ?? '',
                    'payment_account' => $merchant->payment_account ?? '',
                ],
                'stats' => [
                    'orders_today' => $totalOrders,
                    'monthly_revenue' => $totalRevenue,
                    'active_products' => $totalProducts,
                    'loyal_customers' => 0,
                ],
                'recent_orders' => $recentOrders,
                'products' => $products,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dashboard:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur interne du serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function getStatusBadge($status)
    {
        $statuses = [
            'pending' => ['text' => 'En attente', 'color' => 'warning'],
            'confirmed' => ['text' => 'Confirmée', 'color' => 'info'],
            'processing' => ['text' => 'En traitement', 'color' => 'primary'],
            'shipped' => ['text' => 'Expédiée', 'color' => 'secondary'],
            'delivered' => ['text' => 'Livrée', 'color' => 'success'],
            'cancelled' => ['text' => 'Annulée', 'color' => 'danger'],
            'refunded' => ['text' => 'Remboursée', 'color' => 'dark'],
        ];

        return $statuses[$status] ?? ['text' => 'Inconnu', 'color' => 'secondary'];
    }
}