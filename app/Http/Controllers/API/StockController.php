<?php
// app/Http/Controllers/API/StockController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductImageVariant;
use App\Models\ProductImageVariantSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Vérifier la disponibilité du stock pour une variante
     */
    public function checkStock(Request $request)
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'variant_id' => 'required|integer|exists:product_image_variants,id',
                'size_id' => 'nullable|integer|exists:product_image_variant_sizes,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'available' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // CAS 1: Avec taille spécifique
            if ($request->size_id) {
                $size = ProductImageVariantSize::with('variant')
                    ->find($request->size_id);
                
                if (!$size) {
                    return response()->json([
                        'available' => false,
                        'message' => 'Taille non trouvée',
                        'current_stock' => 0
                    ]);
                }

                $available = $size->stock_quantity >= $request->quantity;

                return response()->json([
                    'available' => $available,
                    'current_stock' => $size->stock_quantity,
                    'variant_stock' => $size->variant->stock_quantity,
                    'size_name' => $size->size_name,
                    'message' => $available ? 'Stock disponible' : 'Stock insuffisant pour cette taille'
                ]);
            }

            // CAS 2: Sans taille (variante seule)
            $variant = ProductImageVariant::find($request->variant_id);
            
            if (!$variant) {
                return response()->json([
                    'available' => false,
                    'message' => 'Variante non trouvée',
                    'current_stock' => 0
                ]);
            }

            $available = $variant->stock_quantity >= $request->quantity;

            return response()->json([
                'available' => $available,
                'current_stock' => $variant->stock_quantity,
                'message' => $available ? 'Stock disponible' : 'Stock insuffisant'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification stock', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'available' => false,
                'message' => 'Erreur lors de la vérification du stock',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Vérifier plusieurs variantes à la fois (pour le panier)
     */
    public function checkMultipleStocks(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.variant_id' => 'required|integer|exists:product_image_variants,id',
                'items.*.size_id' => 'nullable|integer|exists:product_image_variant_sizes,id',
                'items.*.quantity' => 'required|integer|min:1'
            ]);

            $results = [];

            foreach ($request->items as $item) {
                if (isset($item['size_id'])) {
                    $size = ProductImageVariantSize::find($item['size_id']);
                    $results[] = [
                        'variant_id' => $item['variant_id'],
                        'size_id' => $item['size_id'],
                        'available' => $size && $size->stock_quantity >= $item['quantity'],
                        'current_stock' => $size ? $size->stock_quantity : 0
                    ];
                } else {
                    $variant = ProductImageVariant::find($item['variant_id']);
                    $results[] = [
                        'variant_id' => $item['variant_id'],
                        'size_id' => null,
                        'available' => $variant && $variant->stock_quantity >= $item['quantity'],
                        'current_stock' => $variant ? $variant->stock_quantity : 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification multiple stocks', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification'
            ], 500);
        }
    }

    /**
     * Vérifier le prix d'une variante (optionnel)
     */
    public function verifyPrice(Request $request)
    {
        try {
            $request->validate([
                'variant_id' => 'required|integer|exists:product_image_variants,id',
                'price' => 'required|numeric'
            ]);

            $variant = ProductImageVariant::find($request->variant_id);

            if (!$variant) {
                return response()->json([
                    'price_changed' => false,
                    'message' => 'Variante non trouvée'
                ]);
            }

            $priceChanged = abs($variant->price - $request->price) > 0.01; // Tolérance pour les arrondis

            return response()->json([
                'price_changed' => $priceChanged,
                'current_price' => $variant->price,
                'message' => $priceChanged ? 'Le prix a changé' : 'Prix inchangé'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification prix', ['error' => $e->getMessage()]);
            return response()->json([
                'price_changed' => false,
                'message' => 'Erreur lors de la vérification du prix'
            ], 500);
        }
    }
}