<?php
// app/Http/Controllers/API/MerchantFollowController.php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

            // ✅ Récupérer le marchand (qui est un User)
            $merchant = User::find($merchantId);
            
            if (!$merchant || !$merchant->is_merchant) {
                return response()->json([
                    'error' => 'Marchand non trouvé',
                    'merchant_id' => $merchantId
                ], 404);
            }

            // Vérifier si l'utilisateur suit déjà ce marchand
            $isFollowing = DB::table('merchant_followers')
                ->where('user_id', $user->id)
                ->where('merchant_id', $merchantId)
                ->exists();

            if ($isFollowing) {
                // Unfollow
                DB::table('merchant_followers')
                    ->where('user_id', $user->id)
                    ->where('merchant_id', $merchantId)
                    ->delete();
                $newIsFollowing = false;
                $action = 'unfollowed';
                $message = 'Vous ne suivez plus ce vendeur';
            } else {
                // Follow
                DB::table('merchant_followers')->insert([
                    'user_id' => $user->id,
                    'merchant_id' => $merchantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $newIsFollowing = true;
                $action = 'followed';
                $message = 'Vous suivez maintenant ce vendeur';
            }

            $followersCount = DB::table('merchant_followers')
                ->where('merchant_id', $merchantId)
                ->count();

            Log::info('📌 Follow action', [
                'user_id' => $user->id,
                'merchant_id' => $merchantId,
                'action' => $action,
                'followers_count' => $followersCount
            ]);

            return response()->json([
                'success' => true,
                'action' => $action,
                'is_following' => $newIsFollowing,
                'followers_count' => $followersCount,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur toggleFollow', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
                return response()->json(['is_following' => false, 'followers_count' => 0]);
            }

            // ✅ Vérifier que le marchand existe
            $merchant = User::find($merchantId);
            if (!$merchant || !$merchant->is_merchant) {
                return response()->json([
                    'is_following' => false,
                    'followers_count' => 0,
                    'merchant_exists' => false
                ]);
            }

            $isFollowing = DB::table('merchant_followers')
                ->where('user_id', $user->id)
                ->where('merchant_id', $merchantId)
                ->exists();

            $followersCount = DB::table('merchant_followers')
                ->where('merchant_id', $merchantId)
                ->count();

            return response()->json([
                'is_following' => $isFollowing,
                'followers_count' => $followersCount
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur checkFollowStatus', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'is_following' => false,
                'followers_count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}