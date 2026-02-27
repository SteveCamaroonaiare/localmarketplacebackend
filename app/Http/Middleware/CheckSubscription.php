<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\MerchantSubscription;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Récupérer le merchant
        $merchant = Merchant::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant non trouvé'
            ], 404);
        }

        // Vérifier si le merchant a un abonnement actif
        $subscription = MerchantSubscription::with('plan')
            ->where('merchant_id', $merchant->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif. Veuillez choisir un plan.',
                'requires_subscription' => true,
                'redirect_to' => '/merchant/subscription'
            ], 403);
        }

        // Vérifier les limites du plan
        $productsCount = $merchant->products()->where('status', 'approved')->count();
        $ordersThisMonth = $merchant->orders()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $warnings = [];

        // Vérifier limite produits
        if ($subscription->plan->product_limit > 0 && $productsCount >= $subscription->plan->product_limit) {
            $warnings[] = [
                'type' => 'product_limit',
                'message' => "Vous avez atteint la limite de {$subscription->plan->product_limit} produits. Passez à un plan supérieur.",
                'current' => $productsCount,
                'limit' => $subscription->plan->product_limit,
            ];
        }

        // Vérifier limite commandes
        if ($subscription->plan->order_limit > 0 && $ordersThisMonth >= $subscription->plan->order_limit) {
            $warnings[] = [
                'type' => 'order_limit',
                'message' => "Vous avez atteint la limite de {$subscription->plan->order_limit} commandes ce mois. Passez à un plan supérieur.",
                'current' => $ordersThisMonth,
                'limit' => $subscription->plan->order_limit,
            ];
        }

        // Avertissement si proche des limites (80%)
        if ($subscription->plan->product_limit > 0) {
            $productPercentage = ($productsCount / $subscription->plan->product_limit) * 100;
            if ($productPercentage >= 80 && $productPercentage < 100) {
                $warnings[] = [
                    'type' => 'product_warning',
                    'message' => "Vous approchez de la limite de produits ({$productsCount}/{$subscription->plan->product_limit}). Pensez à upgrader.",
                    'current' => $productsCount,
                    'limit' => $subscription->plan->product_limit,
                ];
            }
        }

        if ($subscription->plan->order_limit > 0) {
            $orderPercentage = ($ordersThisMonth / $subscription->plan->order_limit) * 100;
            if ($orderPercentage >= 80 && $orderPercentage < 100) {
                $warnings[] = [
                    'type' => 'order_warning',
                    'message' => "Vous approchez de la limite de commandes ce mois ({$ordersThisMonth}/{$subscription->plan->order_limit}).",
                    'current' => $ordersThisMonth,
                    'limit' => $subscription->plan->order_limit,
                ];
            }
        }

        // Avertissement expiration proche (7 jours)
        $daysUntilExpiry = now()->diffInDays($subscription->ends_at, false);
        if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
            $warnings[] = [
                'type' => 'subscription_expiring',
                'message' => "Votre abonnement expire dans {$daysUntilExpiry} jours. Pensez à renouveler.",
                'days_left' => $daysUntilExpiry,
            ];
        }

        // Injecter les données dans la requête
        $request->merge([
            'merchant' => $merchant,
            'subscription' => $subscription,
            'subscription_warnings' => $warnings,
        ]);

        return $next($request);
    }
}