<?php
// app/Http/Controllers/API/Customer/WishlistController.php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    /**
     * Afficher la wishlist de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $wishlist = Wishlist::where('user_id', $user->id)
                ->with(['product.images', 'product.merchant'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    $product = $item->product;
                    $image = $product->images->first();
                    
                    return [
                        'id' => $item->id,
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'original_price' => $product->original_price,
                        'image_path' => $image ? $image->image_path : null,
                        'merchant_name' => $product->merchant->shop_name ?? $product->merchant->name,
                        'in_stock' => $product->stock_quantity > 0,
                        'created_at' => $item->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $wishlist
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des favoris'
            ], 500);
        }
    }

    /**
     * Ajouter un produit aux favoris
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);

            $user = $request->user();
            $productId = $request->product_id;

            // Vérifier si déjà en favoris
            $exists = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit déjà dans vos favoris'
                ], 400);
            }

            $wishlist = Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId
            ]);

            // Charger les relations
            $wishlist->load(['product.images', 'product.merchant']);

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté aux favoris',
                'data' => [
                    'id' => $wishlist->id,
                    'product_id' => $wishlist->product->id,
                    'name' => $wishlist->product->name,
                    'price' => $wishlist->product->price,
                    'original_price' => $wishlist->product->original_price,
                    'image_path' => $wishlist->product->images->first()?->image_path,
                    'merchant_name' => $wishlist->product->merchant->shop_name ?? $wishlist->product->merchant->name,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout aux favoris'
            ], 500);
        }
    }

    /**
     * Supprimer un produit des favoris
     */
    public function destroy(Request $request, $productId)
    {
        try {
            $user = $request->user();
            
            $deleted = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produit retiré des favoris'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé dans vos favoris'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Vérifier si un produit est en favoris
     */
    public function check(Request $request, $productId)
    {
        try {
            $user = $request->user();
            
            $exists = Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            return response()->json([
                'success' => true,
                'is_favorite' => $exists
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'is_favorite' => false
            ], 500);
        }
    }

    /**
     * Migrer la wishlist d'un invité
     */
    public function migrateGuest(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id'
            ]);

            $user = $request->user();
            $items = $request->items;

            DB::beginTransaction();

            foreach ($items as $item) {
                // Vérifier si déjà existant
                $exists = Wishlist::where('user_id', $user->id)
                    ->where('product_id', $item['product_id'])
                    ->exists();

                if (!$exists) {
                    Wishlist::create([
                        'user_id' => $user->id,
                        'product_id' => $item['product_id']
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist migrée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la migration'
            ], 500);
        }
    }
}