<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminProductController extends Controller
{
    /**
     * Liste des produits en attente de validation
     */
    public function pendingProducts(Request $request)
    {
        try {
            $products = Product::with(['merchant', 'category', 'subCategory', 'images'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'products' => $products,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste produits pending: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits'
            ], 500);
        }
    }

    /**
     * Liste de tous les produits (avec filtres)
     */
    public function allProducts(Request $request)
    {
        try {
            $query = Product::with(['merchant', 'category', 'subCategory', 'images'])
                ->orderBy('created_at', 'desc');

            // Filtrer par statut
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filtrer par merchant
            if ($request->has('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            // Recherche
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate(20);

            return response()->json([
                'success' => true,
                'products' => $products,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste tous produits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits'
            ], 500);
        }
    }

    /**
     * Approuver un produit
     */
    public function approveProduct(Request $request, $id)
    {
        try {
            $admin = $request->user(); // L'admin connecté
            
            $product = Product::findOrFail($id);

            if ($product->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce produit est déjà approuvé'
                ], 400);
            }

            $product->update([
                'status' => 'approved',
                'validated_at' => now(),
                'validated_by' => $admin->id,
                'rejection_reason' => null,
            ]);

            // TODO: Envoyer notification au merchant

            return response()->json([
                'success' => true,
                'message' => 'Produit approuvé avec succès',
                'product' => $product->fresh(['merchant', 'images']),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur approbation produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation'
            ], 500);
        }
    }

    /**
     * Rejeter un produit
     */
    public function rejectProduct(Request $request, $id)
    {
        try {
            $admin = $request->user();
            
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            $product = Product::findOrFail($id);

            $product->update([
                'status' => 'rejected',
                'validated_at' => now(),
                'validated_by' => $admin->id,
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            // TODO: Envoyer notification au merchant avec la raison

            return response()->json([
                'success' => true,
                'message' => 'Produit rejeté',
                'product' => $product->fresh(['merchant', 'images']),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La raison du refus est obligatoire',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur rejet produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet'
            ], 500);
        }
    }

    /**
     * Statistiques admin
     */
    public function stats()
    {
        try {
            $stats = [
                'total_products' => Product::count(),
                'pending' => Product::where('status', 'pending')->count(),
                'approved' => Product::where('status', 'approved')->count(),
                'rejected' => Product::where('status', 'rejected')->count(),
                'today_submissions' => Product::whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }

    /**
     * Désactiver un produit (soft delete ou désactivation)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Toggle entre approved et rejected
            $newStatus = $product->status === 'approved' ? 'rejected' : 'approved';
            
            $product->update([
                'status' => $newStatus,
                'validated_at' => now(),
                'validated_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut modifié avec succès',
                'product' => $product,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du statut'
            ], 500);
        }
    }
}