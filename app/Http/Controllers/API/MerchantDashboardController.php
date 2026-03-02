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
        $merchant = Merchant::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant non trouvé'
            ], 404);
        }

        // ✅ DÉBOGAGE : Voir toutes les commandes du marchand
        $allOrders = Order::where('merchant_id', $merchant->id)->get();
        Log::info('📦 TOUTES LES COMMANDES du marchand', [
            'count' => $allOrders->count(),
            'orders' => $allOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total_price' => $order->total_price,
                    'created_at' => $order->created_at->format('Y-m-d'),
                    'month' => $order->created_at->month,
                    'year' => $order->created_at->year,
                ];
            })
        ]);

        // ✅ DÉBOGAGE : Commandes du mois en cours
        $currentMonthOrders = Order::where('merchant_id', $merchant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->get();
            
        Log::info('📅 COMMANDES DU MOIS EN COURS', [
            'month' => now()->month,
            'year' => now()->year,
            'count' => $currentMonthOrders->count(),
            'orders' => $currentMonthOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total_price' => $order->total_price,
                ];
            })
        ]);

        // ✅ DÉBOGAGE : Commandes avec statuts éligibles pour les revenus
        $eligibleOrders = Order::where('merchant_id', $merchant->id)
            ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->get();
            
        Log::info('💰 COMMANDES ÉLIGIBLES POUR REVENUS', [
            'count' => $eligibleOrders->count(),
            'total' => $eligibleOrders->sum('total_price'),
            'orders' => $eligibleOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total_price' => $order->total_price,
                ];
            })
        ]);

            // ✅ Stats commandes
            $totalOrders = Order::where('merchant_id', $merchant->id)->count();
            $pendingOrders = Order::where('merchant_id', $merchant->id)
                ->where('status', 'pending')->count();
            $confirmedOrders = Order::where('merchant_id', $merchant->id)
                ->whereIn('status', ['confirmed', 'processing', 'shipped'])->count();
            $deliveredOrders = Order::where('merchant_id', $merchant->id)
                ->where('status', 'delivered')->count();

            // ✅ Revenus du mois en cours
            $thisMonthRevenue = Order::where('merchant_id', $merchant->id)
                ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_price');

            // ✅ Revenus du mois dernier
            $lastMonthRevenue = Order::where('merchant_id', $merchant->id)
                ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
                ->whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('total_price');

            // ✅ Revenus totaux
            $totalRevenue = Order::where('merchant_id', $merchant->id)
                ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
                ->sum('total_price');

            // ✅ Calcul de la croissance
            $revenueGrowth = 0;
            if ($lastMonthRevenue > 0) {
                $revenueGrowth = round(($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100);
            }

            // ✅ Commandes récentes
            $recentOrders = Order::with(['items.product'])
                ->where('merchant_id', $merchant->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'merchant' => $merchant,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'confirmed_orders' => $confirmedOrders,
                    'delivered_orders' => $deliveredOrders,
                    'this_month_revenue' => $thisMonthRevenue,
                    'last_month_revenue' => $lastMonthRevenue,
                    'total_revenue' => $totalRevenue,
                    'revenue_growth' => $revenueGrowth,
                ],
                'recent_orders' => $recentOrders,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dashboard merchant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null
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