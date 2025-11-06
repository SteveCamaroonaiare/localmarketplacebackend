<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Order; // ✅ Maintenant on peut l'utiliser
use Illuminate\Support\Facades\Log;

class MerchantDashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        try {
            Log::info('🔍 === DÉBUT DEBUG DASHBOARD ===');
            
            $authenticatedUser = $request->user();
            $merchant = Merchant::where('email', $authenticatedUser->email)->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucun profil marchand trouvé',
                ], 404);
            }

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
                        'status' => $order->status_badge['text'],
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
}