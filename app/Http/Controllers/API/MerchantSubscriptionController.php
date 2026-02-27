<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MerchantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPayment;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MerchantSubscriptionController extends Controller
{
    /**
     * Liste des plans disponibles
     */
    public function plans()
        {
            try {
                $plans = SubscriptionPlan::where('is_active', true)
                    ->orderBy('monthly_price', 'asc') // Du moins cher au plus cher
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $plans
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Erreur récupération plans', [
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération des plans'
                ], 500);
            }
        }

    public function current(Request $request)
        {
            try {
                $user = $request->user();
                
                $merchant = Merchant::where('user_id', $user->id)
                    ->orWhere('email', $user->email)
                    ->first();

                if (!$merchant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Merchant non trouvé',
                        'has_subscription' => false,
                    ], 404);
                }

                // Récupérer l'abonnement actif
                $subscription = MerchantSubscription::with('plan')
                    ->where('merchant_id', $merchant->id)
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

                // Calculer les statistiques d'utilisation
                $productsCount = $merchant->products()->where('status', 'approved')->count();
                $ordersThisMonth = $merchant->orders()
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
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération'
                ], 500);
            }
        }

    /**
     * Souscrire à un plan
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
            
            $merchant = Merchant::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

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

            // Annuler l'abonnement actif s'il existe
            MerchantSubscription::where('merchant_id', $merchant->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            // Créer le nouvel abonnement
            $subscription = MerchantSubscription::create([
                'merchant_id' => $merchant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $request->billing_cycle,
                'amount' => $amount,
                'status' => 'active', // Activé immédiatement (ou 'pending' si paiement requis)
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
            ]);

            // Créer le paiement
            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'payment_id' => 'PAY-' . strtoupper(uniqid()),
                'amount' => $amount,
                'status' => $amount == 0 ? 'paid' : 'pending', // Gratuit = payé
                'method' => $request->payment_method,
                'payment_details' => [
                    'phone' => $request->phone ?? null,
                ],
                'paid_at' => $amount == 0 ? now() : null,
            ]);

            DB::commit();

            Log::info('✅ Nouvel abonnement créé', [
                'merchant_id' => $merchant->id,
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
                'message' => 'Erreur lors de la souscription',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel()
    {
        try {
            $user = auth()->user();
            
            $merchant = Merchant::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

            $subscription = MerchantSubscription::where('merchant_id', $merchant->id)
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
                'merchant_id' => $merchant->id,
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
    public function paymentHistory()
    {
        try {
            $user = auth()->user();
            
            $merchant = Merchant::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

            $payments = SubscriptionPayment::whereHas('subscription', function($query) use ($merchant) {
                $query->where('merchant_id', $merchant->id);
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
}