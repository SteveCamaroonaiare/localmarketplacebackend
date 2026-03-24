<?php
// app/Http/Controllers/API/AdminMerchantController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class AdminMerchantController extends Controller
{
    /**
     * Récupérer les merchants en attente de validation
     */
    public function pendingMerchants()
    {
        try {
            $merchants = User::where('is_merchant', true)
               ->where('merchant_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Merchants en attente récupérés',
                'merchants' => $merchants
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des merchants'
            ], 500);
        }
    }

    
    /**
 * Approuver un merchant
 */
public function approveMerchant(Request $request, $id)
{
    try {
        DB::beginTransaction();

        // ✅ Récupérer l'utilisateur directement
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // ✅ Vérifier que c'est bien un marchand
        if (!$user->is_merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un marchand'
            ], 400);
        }

        $admin = $request->user();

        // ✅ Mettre à jour l'utilisateur
        $user->update([
            'merchant_status' => 'approved',
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        DB::commit();

        Log::info('✅ Marchand approuvé', [
            'user_id' => $user->id,
            'shop_name' => $user->shop_name,
            'admin_id' => $admin->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marchand approuvé avec succès',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'shop_name' => $user->shop_name,
                'status' => $user->merchant_status,
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Erreur approbation marchand', [
            'error' => $e->getMessage(),
            'user_id' => $id
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'approbation: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Rejeter un merchant
 */
public function rejectMerchant(Request $request, $id)
{
    try {
        $request->validate([
            'reason' => 'required|string|min:10|max:500'
        ]);

        // ✅ Récupérer l'utilisateur directement
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // ✅ Vérifier que c'est bien un marchand
        if (!$user->is_merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un marchand'
            ], 400);
        }

        $admin = $request->user();

        // ✅ Mettre à jour l'utilisateur
        $user->update([
            'merchant_status' => 'rejected',
            'is_verified' => false,
            'rejection_reason' => $request->reason,
            'rejected_at' => now(),
            'rejected_by' => $admin->id,
        ]);

        Log::info('❌ Marchand rejeté', [
            'user_id' => $user->id,
            'shop_name' => $user->shop_name,
            'reason' => $request->reason,
            'admin_id' => $admin->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marchand rejeté avec succès',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'shop_name' => $user->shop_name,
                'status' => $user->merchant_status,
                'rejection_reason' => $user->rejection_reason,
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Erreur rejet marchand', [
            'error' => $e->getMessage(),
            'user_id' => $id
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du rejet: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Statistiques des merchants
     */
    public function stats()
    {
        try {
            $stats = [
                'total_merchants' => User::where('is_merchant', true)->count(),
                'verified_merchants' => User::where('is_merchant', true)
                    ->where('merchant_status', 'approved')->count(),
                'pending_merchants' => User::where('is_merchant', true)
                    ->where('merchant_status', 'pending')->count(),
                'active_merchants' => User::where('is_merchant', true)
                    ->has('products')->count(),
                'top_merchants' => Merchant::withCount('products')
                    ->orderBy('products_count', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function ($merchant) {
                        return [
                            'id' => $merchant->id,
                            'shop_name' => $merchant->shop_name,
                            'products_count' => $merchant->products_count,
                            'total_revenue' => $merchant->total_revenue,
                            'followers_count' => $merchant->followers_count,
  'is_followed' => auth()->check()
      ? $merchant->isFollowedBy(auth()->user())
      : false,
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Désactiver un merchant
     */
    public function deactivateMerchant($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            if (!$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas un marchand'
                ], 400);
            }

            $user->update([
                'merchant_status' => 'inactive',
                'is_verified' => false,
            ]);

            Log::info('⚠️ Marchand désactivé', [
                'user_id' => $user->id,
                'shop_name' => $user->shop_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Marchand désactivé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur désactivation marchand', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation'
            ], 500);
        }
    }
    
    
}