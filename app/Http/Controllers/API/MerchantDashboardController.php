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
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            // ✅ Vérifier que l'utilisateur est bien un marchand
            if (!$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // ✅ Utiliser l'ID de l'utilisateur comme merchant_id
            $merchantId = $user->id;

            Log::info('🟢 Dashboard marchand', [
                'user_id' => $user->id,
                'shop_name' => $user->shop_name,
                'is_verified' => $user->is_verified,
                'merchant_status' => $user->merchant_status
            ]);

            // ✅ Stats commandes (avec fallback si pas de commandes)
            $totalOrders = Order::where('merchant_id', $merchantId)->count();
            $pendingOrders = Order::where('merchant_id', $merchantId)
                ->where('status', 'pending')->count();
            $confirmedOrders = Order::where('merchant_id', $merchantId)
                ->whereIn('status', ['confirmed', 'processing', 'shipped'])->count();
            $deliveredOrders = Order::where('merchant_id', $merchantId)
                ->where('status', 'delivered')->count();

            // ✅ Revenus
            $thisMonthRevenue = Order::where('merchant_id', $merchantId)
                ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_price');

            $lastMonthRevenue = Order::where('merchant_id', $merchantId)
                ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
                ->whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('total_price');

            $totalRevenue = Order::where('merchant_id', $merchantId)
                ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
                ->sum('total_price');

            $revenueGrowth = 0;
            if ($lastMonthRevenue > 0) {
                $revenueGrowth = round(($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100);
            }

            // ✅ Commandes récentes
            $recentOrders = Order::with(['items.product'])
                ->where('merchant_id', $merchantId)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // ✅ Construire l'objet merchant pour le frontend
            $merchantData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'shop_name' => $user->shop_name,
                'shop_address' => $user->shop_address,
                'country' => $user->country ?? 'Cameroun',
                'logo' => $user->shop_logo,
                'category' => $user->shop_category,
                'is_verified' => (bool)$user->is_verified,
                'merchant_status' => $user->merchant_status ?? 'pending',
                'payment_method' => $user->payment_method,
                'payment_account' => $user->payment_account,
            ];

            return response()->json([
                'success' => true,
                'merchant' => $merchantData,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'confirmed_orders' => $confirmedOrders,
                    'delivered_orders' => $deliveredOrders,
                    'this_month_revenue' => (float)$thisMonthRevenue,
                    'last_month_revenue' => (float)$lastMonthRevenue,
                    'total_revenue' => (float)$totalRevenue,
                    'revenue_growth' => $revenueGrowth,
                ],
                'recent_orders' => $recentOrders,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dashboard merchant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
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