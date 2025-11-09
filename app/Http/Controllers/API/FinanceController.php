<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceController extends Controller
{
    /**
     * Statistiques financières globales
     */
    public function getFinancialStats(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                Log::error('❌ Utilisateur non authentifié dans FinanceController');
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            Log::info('🔍 Finance stats for user:', [
                'user_id' => $user->id,
                'user_type' => get_class($user)
            ]);

            // Vérifier si c'est un Merchant ou un User
            if ($user instanceof \App\Models\Merchant) {
                $merchantId = $user->id;
            } else {
                // Si c'est un User, trouver le Merchant associé
                $merchant = Merchant::where('user_id', $user->id)->first();
                if (!$merchant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun profil marchand trouvé'
                    ], 404);
                }
                $merchantId = $merchant->id;
            }

            $currentMonth = now()->month;
            $lastMonth = now()->subMonth()->month;

            // Revenus du mois actuel
            $currentMonthRevenue = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->whereMonth('created_at', $currentMonth)
                ->sum('total_price');

            // Revenus du mois dernier
            $lastMonthRevenue = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->whereMonth('created_at', $lastMonth)
                ->sum('total_price');

            // Croissance
            $growth = $lastMonthRevenue > 0 
                ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
                : ($currentMonthRevenue > 0 ? 100 : 0);

            // Solde disponible (paiements complétés depuis plus de 7 jours)
            $availableBalance = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->where('created_at', '<=', now()->subDays(7))
                ->sum('total_price');

            // Solde en attente (paiements récents)
            $pendingBalance = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->where('created_at', '>', now()->subDays(7))
                ->sum('total_price');

            // Commission totale payée (3%)
            $totalCommission = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->sum(DB::raw('total_price * 0.03'));

            // Panier moyen
            $averageOrderValue = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->whereMonth('created_at', $currentMonth)
                ->avg('total_price');

            // Total des revenus
            $totalRevenue = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            $stats = [
                'available_balance' => (float) $availableBalance,
                'pending_balance' => (float) $pendingBalance,
                'current_month_revenue' => (float) $currentMonthRevenue,
                'last_month_revenue' => (float) $lastMonthRevenue,
                'growth_rate' => round($growth, 2),
                'total_commission' => (float) $totalCommission,
                'average_order_value' => (float) ($averageOrderValue ?: 0),
                'total_revenue' => (float) $totalRevenue,
            ];

            Log::info('📊 Finance stats calculated:', $stats);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Finance stats error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revenus mensuels pour le graphique
     */
    public function getMonthlyRevenue(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Vérifier si c'est un Merchant ou un User
            if ($user instanceof \App\Models\Merchant) {
                $merchantId = $user->id;
            } else {
                // Si c'est un User, trouver le Merchant associé
                $merchant = Merchant::where('user_id', $user->id)->first();
                if (!$merchant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun profil marchand trouvé'
                    ], 404);
                }
                $merchantId = $merchant->id;
            }

            Log::info('📈 Monthly revenue for merchant:', ['merchant_id' => $merchantId]);

            $revenues = Order::where('merchant_id', $merchantId)
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'month' => \Carbon\Carbon::create($item->year, $item->month, 1)->format('M'),
                        'revenue' => (float) $item->revenue,
                        'orders' => $item->orders,
                        'commission' => (float) $item->revenue * 0.03
                    ];
                })
                ->reverse()
                ->values();

            Log::info('📈 Monthly revenue data:', ['count' => count($revenues)]);

            return response()->json([
                'success' => true,
                'data' => $revenues
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Monthly revenue error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des revenus'
            ], 500);
        }
    }

    /**
     * Transactions récentes (ventes uniquement pour l'instant)
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Vérifier si c'est un Merchant ou un User
            if ($user instanceof \App\Models\Merchant) {
                $merchantId = $user->id;
            } else {
                // Si c'est un User, trouver le Merchant associé
                $merchant = Merchant::where('user_id', $user->id)->first();
                if (!$merchant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun profil marchand trouvé'
                    ], 404);
                }
                $merchantId = $merchant->id;
            }

            Log::info('💳 Transactions for merchant:', ['merchant_id' => $merchantId]);

            // Transactions des ventes uniquement
            $transactions = Order::where('merchant_id', $merchantId)
                ->with('items')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function($order) {
                    return [
                        'id' => 'TXN-' . $order->id,
                        'type' => 'Vente',
                        'amount' => (float) $order->total_price,
                        'commission' => (float) $order->total_price * 0.03,
                        'net' => (float) $order->total_price * 0.97,
                        'date' => $order->created_at->format('Y-m-d'),
                        'status' => $this->getPaymentStatusLabel($order->payment_status),
                        'method' => $this->getPaymentMethodLabel($order->payment_method),
                        'orderId' => $order->order_number,
                    ];
                });

            Log::info('✅ Total transactions:', ['count' => count($transactions)]);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Transactions error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des paiements de retrait
     */
    public function getPayoutHistory(Request $request)
    {
        try {
            // Pour l'instant, retourner un tableau vide car pas encore implémenté
            return response()->json([
                'success' => true,
                'data' => []
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Payout history error:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des retraits'
            ], 500);
        }
    }

    private function getPaymentMethodLabel($method)
    {
        $labels = [
            'cash' => 'Paiement à la livraison',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
            'card' => 'Carte bancaire'
        ];

        return $labels[$method] ?? $method;
    }

    private function getPaymentStatusLabel($status)
    {
        $labels = [
            'paid' => 'Payé',
            'pending' => 'En attente',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé'
        ];

        return $labels[$status] ?? $status;
    }
}