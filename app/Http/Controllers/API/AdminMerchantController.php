<?php
// app/Http/Controllers/API/AdminMerchantController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMerchantController extends Controller
{
    /**
     * Récupérer les merchants en attente de validation
     */
    public function pendingMerchants()
    {
        try {
            $merchants = Merchant::with('user')
                ->where('is_verified', false)
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

            $merchant = Merchant::find($id);

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

            $admin = $request->user();

            $merchant->update([
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ]);

            // Mettre à jour le rôle de l'utilisateur
            $user = $merchant->user;
            if ($user) {
                $user->update(['role' => 'merchant']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merchant approuvé avec succès',
                'merchant' => $merchant
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation du merchant'
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
                'rejection_reason' => 'required|string|min:10|max:500'
            ]);

            $merchant = Merchant::find($id);

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

            // Ici vous pourriez archiver le merchant ou le supprimer
            // Pour l'instant on le laisse en attente

            return response()->json([
                'success' => true,
                'message' => 'Merchant rejeté avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet du merchant'
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
                'total_merchants' => Merchant::count(),
                'verified_merchants' => Merchant::where('is_verified', true)->count(),
                'pending_merchants' => Merchant::where('is_verified', false)->count(),
                'active_merchants' => Merchant::where('is_verified', true)
                    ->has('products', '>', 0)
                    ->count(),
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
            $merchant = Merchant::find($id);

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

            $merchant->update(['is_verified' => false]);

            // Désactiver tous les produits du merchant
            $merchant->products()->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant désactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du merchant'
            ], 500);
        }
    }
}