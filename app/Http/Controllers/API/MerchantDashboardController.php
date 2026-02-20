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

        // ✅ Stats commandes
        $totalOrders = Order::where('merchant_id', $merchant->id)->count();
        $pendingOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'pending')->count();
        $confirmedOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'confirmed')->count();
        $deliveredOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'delivered')->count();

        // ✅ Revenus ce mois
        $thisMonthRevenue = Order::where('merchant_id', $merchant->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_price');

        // ✅ Revenus totaux
        $totalRevenue = Order::where('merchant_id', $merchant->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->sum('total_price');

        // Commandes récentes
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
                'total_revenue' => $totalRevenue,
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