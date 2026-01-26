<?php

namespace App\Http\Controllers\API;

use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MerchantFollowController extends Controller
{
    public function toggleFollow(Request $request, $merchantId)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Non authentifié'
                ], 401);
            }

            $merchant = Merchant::findOrFail($merchantId);

            // Vérifier si l'utilisateur suit déjà ce marchand
            $isFollowing = $merchant->followers()->where('user_id', $user->id)->exists();

            if ($isFollowing) {
                // Unfollow
                $merchant->followers()->detach($user->id);
                $action = 'unfollowed';
            } else {
                // Follow
                $merchant->followers()->attach($user->id);
                $action = 'followed';
            }

            return response()->json([
                'success' => true,
                'action' => $action,
                'is_following' => !$isFollowing,
                'followers_count' => $merchant->followers()->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'action',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkFollowStatus(Request $request, $merchantId)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['is_following' => false]);
            }

            $merchant = Merchant::findOrFail($merchantId);
            $isFollowing = $merchant->followers()->where('user_id', $user->id)->exists();

            return response()->json([
                'is_following' => $isFollowing,
                'followers_count' => $merchant->followers()->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}