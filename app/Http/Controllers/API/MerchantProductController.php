<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ProductImage;
use App\Models\ColorVariant;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MerchantProductController extends Controller
{
    /**
     * Liste des produits du merchant
     */
    public function index(Request $request)
    {
        try {
            $merchant = $request->user();
            
            $query = Product::where('merchant_id', $merchant->id)
                ->with(['category', 'subCategory', 'images', 'colorVariants', 'sizes'])
                ->orderBy('created_at', 'desc');

            // Filtrer par statut si demandé
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $products = $query->paginate(10);

            return response()->json([
                'success' => true,
                'products' => $products,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste produits merchant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits'
            ], 500);
        }
    }

    /**
     * Créer un nouveau produit
     */
    public function store(Request $request)
    {
        try {
            $merchant = $request->user();

            Log::info('🔍 Données reçues pour création produit:', [
                'all_data' => $request->all(),
                'files' => $request->allFiles(),
                'merchant_id' => $merchant->id
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'original_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
                'department_id' => 'required|exists:departments,id',
                'payment_on_delivery' => 'nullable|boolean',
                // 'return_policy' supprimé car c'est un boolean dans la table, pas du texte
                'images' => 'required|array|min:1|max:5',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'colors' => 'nullable|array',
                'colors.*.name' => 'required_with:colors|string',
                'colors.*.color_code' => 'required_with:colors|string',
                'colors.*.stock_quantity' => 'required_with:colors|integer|min:0',
                'sizes' => 'nullable|array',
                'sizes.*.name' => 'required_with:sizes|string',
                'sizes.*.stock_quantity' => 'required_with:sizes|integer|min:0',
            ]);

            Log::info('✅ Validation réussie');

            DB::beginTransaction();

            // Uploader la première image et obtenir son chemin
            $mainImagePath = null;
            if ($request->hasFile('images') && count($request->file('images')) > 0) {
                $firstImage = $request->file('images')[0];
                $mainImagePath = $firstImage->store('products', 'public');
            }

            // Créer le produit avec UNIQUEMENT les colonnes nécessaires
            $product = Product::create([
                'merchant_id' => $merchant->id,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'original_price' => $validated['original_price'] ?? $validated['price'],
                'stock_quantity' => $validated['stock_quantity'],
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'department_id' => $validated['department_id'], 
                'status' => 'pending',
                'image' => $mainImagePath,
                'payment_on_delivery' => isset($validated['payment_on_delivery']) ? (bool)$validated['payment_on_delivery'] : false,
                'return_policy' => false, // ⚠️ C'est un boolean dans votre table, pas du texte !
                'rating' => 0, // ⚠️ Obligatoire avec default 0
                'reviews' => 0, // ⚠️ Obligatoire avec default 0
            ]);

            // Uploader toutes les images dans product_images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            // Ajouter les variantes de couleur
            if (!empty($validated['colors'])) {
                foreach ($validated['colors'] as $color) {
                    ColorVariant::create([
                        'product_id' => $product->id,
                        'color_name' => $color['name'],
                        'color_code' => $color['color_code'],
                        'stock_quantity' => $color['stock_quantity'],
                    ]);
                }
            }

            // Ajouter les tailles
            if (!empty($validated['sizes'])) {
                foreach ($validated['sizes'] as $size) {
                    Size::create([
                        'product_id' => $product->id,
                        'name' => $size['name'],
                        'stock_quantity' => $size['stock_quantity'],
                    ]);
                }
            }

            DB::commit();

            // TODO: Envoyer notification à l'admin

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès et en attente de validation',
                'product' => $product->load(['images', 'colorVariants', 'sizes']),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('❌ Erreur de validation:', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du produit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un produit spécifique
     */
    public function show(Request $request, $id)
    {
        try {
            $merchant = $request->user();
            
            $product = Product::where('merchant_id', $merchant->id)
                ->with(['category', 'subCategory', 'images', 'colorVariants', 'sizes'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'product' => $product,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable'
            ], 404);
        }
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, $id)
    {
        try {
            $merchant = $request->user();
            
            $product = Product::where('merchant_id', $merchant->id)->findOrFail($id);

            // Un produit approuvé ne peut pas être modifié (ou repasse en pending)
            if ($product->status === 'approved') {
                $product->status = 'pending'; // Repasse en validation
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'original_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'sometimes|integer|min:0',
                'category_id' => 'sometimes|exists:categories,id',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
                'department_id' => 'sometimes|exists:departments,id',
                'payment_on_delivery' => 'boolean',
                'return_policy' => 'nullable|string',
            ]);

            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'product' => $product->fresh(['images', 'colorVariants', 'sizes']),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer un produit
     */
    public function destroy(Request $request, $id)
    {
        try {
            $merchant = $request->user();
            
            $product = Product::where('merchant_id', $merchant->id)->findOrFail($id);

            // Supprimer les images du stockage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Statistiques des produits
     */
    public function stats(Request $request)
    {
        try {
            $merchant = $request->user();

            $stats = [
                'total' => Product::where('merchant_id', $merchant->id)->count(),
                'pending' => Product::where('merchant_id', $merchant->id)->where('status', 'pending')->count(),
                'approved' => Product::where('merchant_id', $merchant->id)->where('status', 'approved')->count(),
                'rejected' => Product::where('merchant_id', $merchant->id)->where('status', 'rejected')->count(),
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
}