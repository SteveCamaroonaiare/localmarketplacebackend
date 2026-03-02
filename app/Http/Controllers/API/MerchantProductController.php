<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ProductImage;
use App\Models\ColorVariant;
use App\Models\Size;
use App\Models\Merchant;
use Illuminate\Support\Str;
use App\Models\ProductUpdate;
use App\Models\ProductImageVariant;
use App\Models\ProductImageVariantSize;
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
        Log::info('🟡 Tentative de création produit', [
            'user' => $request->user()?->id,
            'has_files' => $request->hasFile('images'),
            'all_data' => $request->all()
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'department_id' => 'required|exists:departments,id',
            'payment_on_delivery' => 'boolean',
            
            // Images avec leurs données
            'images' => 'required|array|min:1|max:5',
            'images.*.file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images.*.color_name' => 'nullable|string',
            'images.*.color_code' => 'nullable|string',
            'images.*.price' => 'required|numeric|min:0',
            'images.*.original_price' => 'nullable|numeric|min:0',
            'images.*.stock_quantity' => 'required|integer|min:0',
            'images.*.sizes' => 'nullable|array', // Tableau de tailles
            'images.*.sizes.*.name' => 'required|string',
            'images.*.sizes.*.stock' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        $user = $request->user();
        
        $merchant = Merchant::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$merchant) {
            throw new \Exception('Merchant non trouvé');
        }

        // Prix de base = prix minimum des images
        $basePrice = collect($validated['images'])->min('price');
        $totalStock = collect($validated['images'])->sum('stock_quantity');

        // Créer le produit
        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . time(),
            'description' => $validated['description'],
            'price' => $basePrice,
            'stock_quantity' => $totalStock,
            'category_id' => $validated['category_id'],
            'sub_category_id' => $validated['sub_category_id'] ?? null,
            'department_id' => $validated['department_id'],
            'payment_on_delivery' => $validated['payment_on_delivery'] ?? false,
            'status' => 'pending',
            'is_in_stock' => true,
        ]);

        // Traiter chaque image comme une variante
        foreach ($validated['images'] as $index => $imageData) {
            // Uploader l'image
            $imagePath = $imageData['file']->store('products', 'public');
            
            // Créer l'entrée dans product_images
            $productImage = ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'sort_order' => $index,
            ]);

            // Créer la variante liée à cette image
            $variant = ProductImageVariant::create([
                'product_id' => $product->id,
                'image_id' => $productImage->id,
                'color_name' => $imageData['color_name'] ?? null,
                'color_code' => $imageData['color_code'] ?? null,
                'price' => $imageData['price'],
                'original_price' => $imageData['original_price'] ?? null,
                'stock_quantity' => $imageData['stock_quantity'],
            ]);

            // Ajouter les tailles si fournies
            if (isset($imageData['sizes']) && is_array($imageData['sizes'])) {
                foreach ($imageData['sizes'] as $sizeData) {
                    ProductImageVariantSize::create([
                        'variant_id' => $variant->id,
                        'size_name' => $sizeData['name'],
                        'stock_quantity' => $sizeData['stock'],
                    ]);
                }
            }
        }

        DB::commit();

        Log::info('✅ Produit créé avec variantes d\'images', [
            'product_id' => $product->id,
            'merchant_id' => $merchant->id,
            'images_count' => count($validated['images']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $product->load('images', 'imageVariants.sizes'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ Erreur création produit', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
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
  

    /**
     * Supprimer un produit
     */
 public function destroy(Request $request, $id)
{
    try {
        $user = $request->user();
        
        // Trouver le merchant
        $merchant = \App\Models\Merchant::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();
        
        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant non trouvé'
            ], 404);
        }

        // Vérifier que le produit appartient au merchant
        $product = Product::where('merchant_id', $merchant->id)
            ->with(['images', 'colorVariants', 'sizes'])
            ->findOrFail($id);

        // ✅ Utiliser une transaction pour tout supprimer proprement
        DB::beginTransaction();

        try {
            // 1. Supprimer les images physiques ET les enregistrements
            if ($product->images) {
                foreach ($product->images as $image) {
                    if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                        Storage::disk('public')->delete($image->image_path);
                    }
                    $image->delete(); // ✅ Supprimer l'enregistrement
                }
            }

            // 2. Supprimer les variantes de couleur
            if ($product->colorVariants) {
                $product->colorVariants()->delete();
            }

            // 3. Supprimer les tailles
            if ($product->sizes) {
                $product->sizes()->delete();
            }

            // 4. Supprimer les autres relations si elles existent
            // Reviews
            if (method_exists($product, 'reviews')) {
                $product->reviews()->delete();
            }

            // Wishlist items
            if (method_exists($product, 'wishlistItems')) {
                $product->wishlistItems()->delete();
            }

            // Cart items
            if (method_exists($product, 'cartItems')) {
                $product->cartItems()->delete();
            }

            // 5. Enfin, supprimer le produit lui-même
            $product->delete();

            DB::commit();

            Log::info('✅ Produit supprimé avec succès', [
                'product_id' => $id,
                'merchant_id' => $merchant->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Produit non trouvé'
        ], 404);
    } catch (\Exception $e) {
        Log::error('❌ Erreur suppression produit', [
            'error' => $e->getMessage(),
            'product_id' => $id,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

// ✅ Faire pareil pour update
public function update(Request $request, $id)
{
    try {
        $user = $request->user();

        $merchant = \App\Models\Merchant::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant non trouvé'
            ], 404);
        }

        $product = Product::where('merchant_id', $merchant->id)->findOrFail($id);

        DB::beginTransaction();

        // Validation des données
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'department_id' => 'sometimes|exists:departments,id',
            'payment_on_delivery' => 'boolean',
            
            // Gestion des images
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'deleted_image_ids' => 'nullable|array',
            'deleted_image_ids.*' => 'integer|exists:product_images,id',
            
            // Gestion des variantes d'images
            'image_variants' => 'nullable|array',
            'image_variants.*.id' => 'nullable|integer|exists:product_image_variants,id',
            'image_variants.*.image_id' => 'required|integer|exists:product_images,id',
            'image_variants.*.color_name' => 'nullable|string',
            'image_variants.*.color_code' => 'nullable|string',
            'image_variants.*.price' => 'required|numeric|min:0',
            'image_variants.*.original_price' => 'nullable|numeric|min:0',
            'image_variants.*.stock_quantity' => 'required|integer|min:0',
            'image_variants.*.sizes' => 'nullable|array',
            'image_variants.*.sizes.*.id' => 'nullable|integer|exists:product_image_variant_sizes,id',
            'image_variants.*.sizes.*.size_name' => 'required|string',
            'image_variants.*.sizes.*.stock_quantity' => 'required|integer|min:0',
            
            // IDs à supprimer
            'deleted_variant_ids' => 'nullable|array',
            'deleted_variant_ids.*' => 'integer|exists:product_image_variants,id',
            'deleted_size_ids' => 'nullable|array',
            'deleted_size_ids.*' => 'integer|exists:product_image_variant_sizes,id',
        ]);

        // Mettre à jour les informations de base
        $updateData = collect($validated)->except([
            'images', 
            'deleted_image_ids', 
            'image_variants', 
            'deleted_variant_ids', 
            'deleted_size_ids'
        ])->toArray();
        
        // Si le produit était approuvé, repasser en pending
        if ($product->status === 'approved') {
            $updateData['status'] = 'pending';
        }
        
        $product->update($updateData);

        // ===========================================
        // 1. SUPPRIMER LES IMAGES MARQUÉES
        // ===========================================
        if ($request->has('deleted_image_ids')) {
            foreach ($request->deleted_image_ids as $imageId) {
                $image = $product->images()->find($imageId);
                if ($image) {
                    // Supprimer le fichier physique
                    \Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                }
            }
        }

        // ===========================================
        // 2. AJOUTER LES NOUVELLES IMAGES
        // ===========================================
        $newImageIds = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $image = $product->images()->create([
                    'image_path' => $path,
                    'sort_order' => $product->images()->count(),
                ]);
                $newImageIds[] = $image->id;
            }
        }

        // ===========================================
        // 3. SUPPRIMER LES VARIANTES MARQUÉES
        // ===========================================
        if ($request->has('deleted_variant_ids')) {
            foreach ($request->deleted_variant_ids as $variantId) {
                $variant = ProductImageVariant::find($variantId);
                if ($variant && $variant->product_id === $product->id) {
                    $variant->delete();
                }
            }
        }

        // ===========================================
        // 4. SUPPRIMER LES TAILLES MARQUÉES
        // ===========================================
        if ($request->has('deleted_size_ids')) {
            foreach ($request->deleted_size_ids as $sizeId) {
                $size = ProductImageVariantSize::find($sizeId);
                if ($size) {
                    $size->delete();
                }
            }
        }

        // ===========================================
        // 5. METTRE À JOUR LES VARIANTES
        // ===========================================
        if ($request->has('image_variants')) {
            foreach ($request->image_variants as $variantData) {
                // Si l'image_id est nouveau, trouver l'ID correspondant
                $imageId = $variantData['image_id'];
                
                // Si c'est une variante existante
                if (isset($variantData['id'])) {
                    $variant = ProductImageVariant::find($variantData['id']);
                    if ($variant && $variant->product_id === $product->id) {
                        $variant->update([
                            'image_id' => $imageId,
                            'color_name' => $variantData['color_name'] ?? null,
                            'color_code' => $variantData['color_code'] ?? null,
                            'price' => $variantData['price'],
                            'original_price' => $variantData['original_price'] ?? null,
                            'stock_quantity' => $variantData['stock_quantity'],
                        ]);
                    }
                } else {
                    // Nouvelle variante
                    $variant = ProductImageVariant::create([
                        'product_id' => $product->id,
                        'image_id' => $imageId,
                        'color_name' => $variantData['color_name'] ?? null,
                        'color_code' => $variantData['color_code'] ?? null,
                        'price' => $variantData['price'],
                        'original_price' => $variantData['original_price'] ?? null,
                        'stock_quantity' => $variantData['stock_quantity'],
                    ]);
                }

                // Gérer les tailles de cette variante
                if (isset($variantData['sizes'])) {
                    foreach ($variantData['sizes'] as $sizeData) {
                        if (isset($sizeData['id'])) {
                            // Mettre à jour une taille existante
                            $size = ProductImageVariantSize::find($sizeData['id']);
                            if ($size && $size->variant_id === $variant->id) {
                                $size->update([
                                    'size_name' => $sizeData['size_name'],
                                    'stock_quantity' => $sizeData['stock_quantity'],
                                ]);
                            }
                        } else {
                            // Nouvelle taille
                            ProductImageVariantSize::create([
                                'variant_id' => $variant->id,
                                'size_name' => $sizeData['size_name'],
                                'stock_quantity' => $sizeData['stock_quantity'],
                            ]);
                        }
                    }
                }
            }
        }

        // ===========================================
        // 6. METTRE À JOUR LES PRIX MIN/MAX
        // ===========================================
        $variants = $product->imageVariants;
        if ($variants->count() > 0) {
            $minPrice = $variants->min('price');
            $maxPrice = $variants->max('price');
            $totalStock = $variants->sum('stock_quantity');
            
            $product->update([
                'price' => $minPrice,
                'stock_quantity' => $totalStock,
            ]);
        }

        DB::commit();

        // Charger le produit avec ses relations
        $product->load(['images', 'imageVariants.image', 'imageVariants.sizes']);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès',
            'product' => $product
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ Erreur mise à jour produit', [
            'id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour',
            'error' => config('app.debug') ? $e->getMessage() : null
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


    public function stockAlerts(Request $request)
    {
        try {
            $user = $request->user();
            
            $merchant = Merchant::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant non trouvé'
                ], 404);
            }

            // Produits en rupture de stock
            $outOfStock = Product::with('images')
                ->where('merchant_id', $merchant->id)
                ->where('stock_quantity', 0)
                ->orWhere('is_in_stock', false)
                ->get();

            // Produits avec stock faible (≤ 5)
            $lowStock = Product::with('images')
                ->where('merchant_id', $merchant->id)
                ->where('stock_quantity', '>', 0)
                ->where('stock_quantity', '<=', 5)
                ->get();

            return response()->json([
                'success' => true,
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur alertes stock', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }
}