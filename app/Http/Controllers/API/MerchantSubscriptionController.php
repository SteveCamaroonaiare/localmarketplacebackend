<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MerchantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPayment;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantSubscriptionController extends Controller
{
    /**
     * Récupérer l'abonnement actuel du marchand
     */
    public function current(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'has_subscription' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }
            
            $merchantId = $user->id;

            // ✅ Récupérer l'abonnement actif
            $subscription = MerchantSubscription::with('plan')
                ->where('merchant_id', $merchantId)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => true,
                    'has_subscription' => false,
                    'message' => 'Aucun abonnement actif',
                ]);
            }

            // ✅ Calculer les statistiques d'utilisation
            $productsCount = Product::where('merchant_id', $merchantId)
                ->where('status', 'approved')
                ->count();
                
            $ordersThisMonth = Order::where('merchant_id', $merchantId)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            $usage = [
                'products_used' => $productsCount,
                'products_limit' => $subscription->plan->product_limit,
                'products_percentage' => $subscription->plan->product_limit > 0 
                    ? round(($productsCount / $subscription->plan->product_limit) * 100, 1)
                    : 0,
                
                'orders_used' => $ordersThisMonth,
                'orders_limit' => $subscription->plan->order_limit,
                'orders_percentage' => $subscription->plan->order_limit > 0
                    ? round(($ordersThisMonth / $subscription->plan->order_limit) * 100, 1)
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'has_subscription' => true,
                'data' => [
                    'subscription' => $subscription,
                    'usage' => $usage,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur abonnement actuel', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'abonnement'
            ], 500);
        }
    }

    /**
     * Créer un nouvel abonnement
     */
    public function subscribe(Request $request)
    {
        try {
            $request->validate([
                'plan_id' => 'required|exists:subscription_plans,id',
                'billing_cycle' => 'required|in:monthly,yearly',
                'payment_method' => 'required|in:mobile_money,bank_transfer,card,cash',
            ]);

            $user = auth()->user();
            
            if (!$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }
            
            $merchantId = $user->id;

            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // Calculer le montant
            $amount = $request->billing_cycle === 'monthly' 
                ? $plan->monthly_price 
                : $plan->yearly_price;

            // Durée de l'abonnement
            $starts_at = now();
            $ends_at = $request->billing_cycle === 'monthly'
                ? $starts_at->copy()->addMonth()
                : $starts_at->copy()->addYear();

            DB::beginTransaction();

            // ✅ Annuler l'abonnement actif s'il existe
            MerchantSubscription::where('merchant_id', $merchantId)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            // Créer le nouvel abonnement
            $subscription = MerchantSubscription::create([
                'merchant_id' => $merchantId,
                'plan_id' => $plan->id,
                'billing_cycle' => $request->billing_cycle,
                'amount' => $amount,
                'status' => 'active',
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
            ]);

            // Créer le paiement
            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'payment_id' => 'PAY-' . strtoupper(uniqid()),
                'amount' => $amount,
                'status' => $amount == 0 ? 'paid' : 'pending',
                'method' => $request->payment_method,
                'payment_details' => [
                    'phone' => $request->phone ?? null,
                ],
                'paid_at' => $amount == 0 ? now() : null,
            ]);

            DB::commit();

            Log::info('✅ Nouvel abonnement créé', [
                'merchant_id' => $merchantId,
                'plan' => $plan->name,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
                'data' => [
                    'subscription' => $subscription->load('plan'),
                    'payment' => $payment,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Erreur souscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la souscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }
            
            $merchantId = $user->id;

            $subscription = MerchantSubscription::where('merchant_id', $merchantId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement actif'
                ], 404);
            }

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            Log::info('❌ Abonnement annulé', [
                'merchant_id' => $merchantId,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement annulé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur annulation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    /**
     * Historique des paiements
     */
    public function paymentHistory(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }
            
            $merchantId = $user->id;

            $payments = SubscriptionPayment::whereHas('subscription', function($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId);
            })
            ->with(['subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur historique paiements', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Récupérer les plans d'abonnement
     */
    public function plans()
    {
        try {
            $plans = SubscriptionPlan::where('is_active', true)
                ->orderBy('monthly_price' ,'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $plans
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur plans', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plans'
            ], 500);
        }
    }
}