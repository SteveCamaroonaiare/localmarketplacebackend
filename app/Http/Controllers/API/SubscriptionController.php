<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\MerchantSubscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Liste des plans d'abonnement
     */
    public function getPlans()
    {
        try {
            $plans = SubscriptionPlan::active()
                ->orderBy('sort_order')
                ->get()
                ->map(function($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                        'description' => $plan->description,
                        'monthly_price' => (float) $plan->monthly_price,
                        'yearly_price' => (float) $plan->yearly_price,
                        'yearly_discount' => $plan->yearly_discount,
                        'product_limit' => $plan->product_limit,
                        'order_limit' => $plan->order_limit,
                        'commission_rate' => (float) $plan->commission_rate,
                        'features' => $plan->features ?? [],
                        'is_popular' => $plan->is_popular,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $plans
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plans'
            ], 500);
        }
    }

    /**
     * Souscrire à un plan
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|exists:subscription_plans,id',
                'billing_cycle' => 'required|in:monthly,yearly',
                'payment_method' => 'required|in:card,mobile_money,bank_transfer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $merchant = $request->user();
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            DB::beginTransaction();

            // Désactiver les anciens abonnements
            MerchantSubscription::where('merchant_id', $merchant->id)
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            // Calculer le prix
            $amount = $request->billing_cycle === 'monthly' 
                ? $plan->monthly_price 
                : $plan->yearly_price;

            // Créer l'abonnement
            $subscription = MerchantSubscription::create([
                'merchant_id' => $merchant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $request->billing_cycle,
                'amount' => $amount,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $request->billing_cycle === 'monthly' 
                    ? now()->addMonth() 
                    : now()->addYear(),
            ]);

            // Créer le paiement
            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'payment_id' => 'SUB-' . strtoupper(uniqid()),
                'amount' => $amount,
                'status' => 'pending',
                'method' => $request->payment_method,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
                'data' => [
                    'subscription' => $subscription->load('plan'),
                    'payment' => $payment
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la souscription'
            ], 500);
        }
    }

    /**
     * Statut de l'abonnement actuel
     */
    public function getCurrentSubscription(Request $request)
    {
        try {
            $merchant = $request->user();

            $subscription = MerchantSubscription::with('plan')
                ->where('merchant_id', $merchant->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->first();

            $usage = [
                'products_used' => \App\Models\Product::where('merchant_id', $merchant->id)->count(),
                'orders_this_month' => \App\Models\Order::where('merchant_id', $merchant->id)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription,
                    'usage' => $usage,
                    'is_active' => $subscription ? $subscription->isActive() : false,
                    'days_remaining' => $subscription ? $subscription->daysUntilExpiration() : 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'abonnement'
            ], 500);
        }
    }

    /**
     * Historique des paiements d'abonnement
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $merchant = $request->user();

            $payments = SubscriptionPayment::with('subscription.plan')
                ->whereHas('subscription', function($query) use ($merchant) {
                    $query->where('merchant_id', $merchant->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    /**
     * Annuler l'abonnement
     */
    public function cancelSubscription(Request $request)
    {
        try {
            $merchant = $request->user();

            $subscription = MerchantSubscription::where('merchant_id', $merchant->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement actif trouvé'
                ], 404);
            }

            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement annulé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }
}