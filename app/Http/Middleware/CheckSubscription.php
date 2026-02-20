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
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $merchant = Merchant::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant non trouvé'
            ], 404);
        }

        // Vérifier l'abonnement actif
        $subscription = MerchantSubscription::where('merchant_id', $merchant->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement expiré ou inactif. Veuillez renouveler votre abonnement.',
                'requires_subscription' => true
            ], 403);
        }

        // Vérifier les limites du plan
        $request->merge(['merchant_subscription' => $subscription]);

        return $next($request);
    }
}