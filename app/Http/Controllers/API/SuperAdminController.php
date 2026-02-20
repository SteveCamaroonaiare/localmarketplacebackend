<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\MerchantSubscription;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    /**
     * Dashboard principal du super admin
     */
    public function dashboardStats()
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Stats générales
            $stats = [
                'total_admins' => User::where('role', 'admin')->count(),
                'total_merchants' => Merchant::count(),
                'total_orders' => Order::count(),
                'total_revenue' => Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->sum('total_price'),
                
                // Ce mois
                'this_month_orders' => Order::whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $currentMonth)
                    ->count(),
                'this_month_revenue' => Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $currentMonth)
                    ->sum('total_price'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dashboard super admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Calcul des paiements à effectuer aux merchants
     */
    public function merchantPayouts(Request $request)
    {
        try {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $merchants = Merchant::where('is_verified', true)->get();
            $payouts = [];

            foreach ($merchants as $merchant) {
                // Récupérer l'abonnement actif
                $subscription = MerchantSubscription::with('plan')
                    ->where('merchant_id', $merchant->id)
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->first();

                if (!$subscription) {
                    continue; // Pas d'abonnement actif
                }

                // Commandes du mois
                $orders = Order::where('merchant_id', $merchant->id)
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->get();

                $totalSales = $orders->sum('total_price');
                $totalOrders = $orders->count();

                // Commission selon le plan
                $commissionRate = $subscription->plan->commission_rate / 100;
                $platformCommission = $totalSales * $commissionRate;
                $merchantPayout = $totalSales - $platformCommission;

                // Frais d'abonnement du mois
                $subscriptionFee = $subscription->billing_cycle === 'monthly'
                    ? $subscription->plan->monthly_price
                    : $subscription->plan->yearly_price / 12;

                // Montant net à payer au merchant
                $netPayout = $merchantPayout - $subscriptionFee;

                $payouts[] = [
                    'merchant_id' => $merchant->id,
                    'merchant_name' => $merchant->name,
                    'shop_name' => $merchant->shop_name,
                    'email' => $merchant->email,
                    'phone' => $merchant->phone,
                    'plan' => $subscription->plan->name,
                    'commission_rate' => $subscription->plan->commission_rate,
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders,
                    'platform_commission' => $platformCommission,
                    'subscription_fee' => $subscriptionFee,
                    'gross_payout' => $merchantPayout,
                    'net_payout' => $netPayout,
                    'status' => $netPayout > 0 ? 'to_pay' : 'no_payment',
                ];
            }

            // Totaux
            $totals = [
                'total_sales' => array_sum(array_column($payouts, 'total_sales')),
                'total_commission' => array_sum(array_column($payouts, 'platform_commission')),
                'total_subscription_fees' => array_sum(array_column($payouts, 'subscription_fee')),
                'total_net_payout' => array_sum(array_column($payouts, 'net_payout')),
                'merchants_to_pay' => count(array_filter($payouts, fn($p) => $p['net_payout'] > 0)),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'payouts' => $payouts,
                    'totals' => $totals,
                    'month' => $month,
                    'year' => $year,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur calcul paiements merchants', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul'
            ], 500);
        }
    }

    /**
     * Marquer un paiement comme effectué
     */
    public function markPayoutAsPaid(Request $request, $merchantId)
    {
        try {
            $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'reference' => 'nullable|string',
            ]);

            // Enregistrer dans une table de paiements (à créer)
            DB::table('merchant_payouts')->insert([
                'merchant_id' => $merchantId,
                'month' => $request->month,
                'year' => $request->year,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('✅ Paiement merchant effectué', [
                'merchant_id' => $merchantId,
                'amount' => $request->amount,
                'paid_by' => auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur enregistrement paiement', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement'
            ], 500);
        }
    }

    /**
     * Activité des administrateurs
     */
    public function adminActivity()
    {
        try {
            $admins = User::where('role', 'admin')
                ->orWhere('admin_role', 'super_admin')
                ->select('id', 'name', 'email', 'role', 'admin_role', 'last_login_at', 'created_at')
                ->get();

            $activities = [];
            foreach ($admins as $admin) {
                $activities[] = [
                    'admin_id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->admin_role ?? $admin->role,
                    'last_login' => $admin->last_login_at,
                    'actions_count' => 0, // À implémenter avec une table de logs
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur activité admins', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Audit système
     */
    public function systemAudit(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $startDate = now()->subDays($days);

            $audit = [
                'merchants' => [
                    'total' => Merchant::count(),
                    'verified' => Merchant::where('is_verified', true)->count(),
                    'pending' => Merchant::where('is_verified', false)->count(),
                    'new_this_period' => Merchant::where('created_at', '>=', $startDate)->count(),
                ],
                'products' => [
                    'total' => Product::count(),
                    'approved' => Product::where('status', 'approved')->count(),
                    'pending' => Product::where('status', 'pending')->count(),
                    'rejected' => Product::where('status', 'rejected')->count(),
                ],
                'orders' => [
                    'total' => Order::where('created_at', '>=', $startDate)->count(),
                    'completed' => Order::where('status', 'delivered')
                        ->where('created_at', '>=', $startDate)->count(),
                    'cancelled' => Order::where('status', 'cancelled')
                        ->where('created_at', '>=', $startDate)->count(),
                ],
                'revenue' => [
                    'total' => Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                        ->where('created_at', '>=', $startDate)
                        ->sum('total_price'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $audit,
                'period_days' => $days
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur audit système', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'audit'
            ], 500);
        }
    }

    /**
     * Statistiques financières détaillées
     */
    public function financialStats(Request $request)
    {
        try {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            // Revenus par plan d'abonnement
            $revenueByPlan = DB::table('merchant_subscriptions')
                ->join('subscription_plans', 'merchant_subscriptions.plan_id', '=', 'subscription_plans.id')
                ->join('orders', 'merchant_subscriptions.merchant_id', '=', 'orders.merchant_id')
                ->whereYear('orders.created_at', $year)
                ->whereMonth('orders.created_at', $month)
                ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->select(
                    'subscription_plans.name as plan_name',
                    DB::raw('COUNT(DISTINCT merchant_subscriptions.merchant_id) as merchants_count'),
                    DB::raw('SUM(orders.total_price) as total_revenue'),
                    DB::raw('AVG(subscription_plans.commission_rate) as avg_commission_rate')
                )
                ->groupBy('subscription_plans.name')
                ->get();

            // Commission totale
            $totalCommission = 0;
            foreach ($revenueByPlan as $plan) {
                $totalCommission += $plan->total_revenue * ($plan->avg_commission_rate / 100);
            }

            // Revenus abonnements
            $subscriptionRevenue = DB::table('subscription_payments')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('status', 'paid')
                ->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_by_plan' => $revenueByPlan,
                    'total_commission' => $totalCommission,
                    'subscription_revenue' => $subscriptionRevenue,
                    'total_platform_revenue' => $totalCommission + $subscriptionRevenue,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur stats financières', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }
}