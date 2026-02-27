<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Merchant;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Récupérer les adresses sauvegardées de l'utilisateur
     */
    public function getAddresses(Request $request)
    {
        try {
            $user = $request->user();
            
            $addresses = DB::table('user_addresses')
                ->where('user_id', $user->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération adresses', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Sauvegarder une nouvelle adresse
     */
    public function saveAddress(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'city' => 'required|string|max:100',
                'district' => 'required|string|max:100',
                'street' => 'required|string|max:255',
                'instructions' => 'nullable|string|max:500',
                'is_default' => 'boolean',
            ]);

            $user = $request->user();

            // Si c'est l'adresse par défaut, retirer le flag des autres
            if ($validated['is_default'] ?? false) {
                DB::table('user_addresses')
                    ->where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $addressId = DB::table('user_addresses')->insertGetId([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'city' => $validated['city'],
                'district' => $validated['district'],
                'street' => $validated['street'],
                'instructions' => $validated['instructions'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $address = DB::table('user_addresses')->find($addressId);

            return response()->json([
                'success' => true,
                'message' => 'Adresse enregistrée',
                'data' => $address
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur sauvegarde adresse', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement'
            ], 500);
        }
    }

    /**
     * Mettre à jour une adresse
     */
    public function updateAddress(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'city' => 'required|string|max:100',
                'district' => 'required|string|max:100',
                'street' => 'required|string|max:255',
                'instructions' => 'nullable|string|max:500',
                'is_default' => 'boolean',
            ]);

            $user = $request->user();

            // Vérifier que l'adresse appartient à l'utilisateur
            $address = DB::table('user_addresses')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse introuvable'
                ], 404);
            }

            // Si c'est l'adresse par défaut, retirer le flag des autres
            if ($validated['is_default'] ?? false) {
                DB::table('user_addresses')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            DB::table('user_addresses')
                ->where('id', $id)
                ->update([
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'],
                    'city' => $validated['city'],
                    'district' => $validated['district'],
                    'street' => $validated['street'],
                    'instructions' => $validated['instructions'] ?? null,
                    'is_default' => $validated['is_default'] ?? false,
                    'updated_at' => now(),
                ]);

            $updatedAddress = DB::table('user_addresses')->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Adresse mise à jour',
                'data' => $updatedAddress
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur mise à jour adresse', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer une adresse
     */
    public function deleteAddress(Request $request, $id)
    {
        try {
            $user = $request->user();

            $deleted = DB::table('user_addresses')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimée'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression adresse', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Calculer les options de livraison selon la localisation
     */
    public function getDeliveryOptions(Request $request)
    {
        try {
            $validated = $request->validate([
                'city' => 'required|string',
                'cart_total' => 'required|numeric',
            ]);

            $city = strtolower($validated['city']);
            $cartTotal = $validated['cart_total'];

            // Options de base
            $options = [
                [
                    'id' => 'standard',
                    'name' => 'Livraison standard',
                    'description' => 'Livraison à domicile sous 2-5 jours ouvrables',
                    'duration' => '2-5 jours',
                    'price' => 0, // Gratuit si > 20000 FCFA
                    'original_price' => 5000,
                    'badge' => 'Gratuit',
                    'badge_color' => 'green',
                ],
                [
                    'id' => 'express',
                    'name' => 'Livraison express',
                    'description' => 'Livraison rapide sous 24-48h',
                    'duration' => '24-48h',
                    'price' => 8000,
                    'badge' => 'Rapide',
                    'badge_color' => 'blue',
                ],
                [
                    'id' => 'pickup',
                    'name' => 'Retrait en boutique',
                    'description' => 'Récupérez votre commande directement chez le vendeur',
                    'duration' => 'Dès aujourd\'hui',
                    'price' => 0,
                    'badge' => 'Économique',
                    'badge_color' => 'purple',
                ],
            ];

            // Appliquer la gratuité selon le montant
            if ($cartTotal < 20000) {
                $options[0]['price'] = 5000;
                $options[0]['badge'] = 'Standard';
                $options[0]['badge_color'] = 'gray';
            }

            // Ajuster les prix selon la ville
            $majorCities = ['douala', 'yaoundé', 'yaounde', 'bafoussam', 'bamenda'];
            if (!in_array($city, $majorCities)) {
                $options[0]['price'] += 2000; // +2000 pour villes secondaires
                $options[1]['price'] += 5000; // +5000 pour express
            }

            return response()->json([
                'success' => true,
                'data' => $options
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur options livraison', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul'
            ], 500);
        }
    }

    /**
     * Valider le panier avant checkout (stock, prix, disponibilité)
     */
    public function validateCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $errors = [];
            $validItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::with('merchant')->find($item['product_id']);

                if (!$product) {
                    $errors[] = "Produit ID {$item['product_id']} introuvable";
                    continue;
                }

                // Vérifier le statut
                if ($product->status !== 'approved') {
                    $errors[] = "{$product->name} n'est plus disponible";
                    continue;
                }

                // Vérifier le stock disponible (stock réel - réservations)
                $availableStock = $product->stock_quantity - ($product->reserved_quantity ?? 0);

                if ($availableStock < $item['quantity']) {
                    $errors[] = "{$product->name}: stock insuffisant (disponible: {$availableStock})";
                    continue;
                }

                if (!$product->is_in_stock || $product->stock_quantity == 0) {
                    $errors[] = "{$product->name} est en rupture de stock";
                    continue;
                }

                $validItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $product->price * $item['quantity'],
                    'merchant_id' => $product->merchant_id,
                    'available_stock' => $availableStock,
                ];
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'valid_items' => $validItems,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Panier valide',
                'data' => $validItems
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur validation panier', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation'
            ], 500);
        }
    }

    /**
     * Créer la commande
     */
    public function createOrder(Request $request)
{
    try {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.full_name' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.district' => 'required|string',
            'shipping_address.street' => 'required|string',
            'delivery_option' => 'required|string',
            'delivery_cost' => 'required|numeric|min:0',
            // ✅ Mettre à jour la validation
            'payment_method' => 'required|in:orange_money,mtn_momo,express_union,card,bank_transfer,cash_on_delivery',
        ]);

        DB::beginTransaction();

        $user = $request->user();
        
        // Grouper les items par merchant
        $itemsByMerchant = [];
        foreach ($validated['items'] as $item) {
            $product = Product::lockForUpdate()->find($item['product_id']);
            
            if (!$product) {
                throw new \Exception("Produit introuvable");
            }
            
            if (!isset($itemsByMerchant[$product->merchant_id])) {
                $itemsByMerchant[$product->merchant_id] = [];
            }
            
            $itemsByMerchant[$product->merchant_id][] = [
                'product' => $product,
                'quantity' => $item['quantity'],
            ];
        }

        $orders = [];

        // Créer une commande par merchant
        foreach ($itemsByMerchant as $merchantId => $items) {
            $subtotal = 0;
            
            // Calculer le sous-total et réserver le stock
            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                
                $availableStock = $product->stock_quantity - ($product->reserved_quantity ?? 0);
                
                if ($availableStock < $quantity) {
                    throw new \Exception("Stock insuffisant pour {$product->name}. Disponible: {$availableStock}");
                }
                
                // ✅ RÉSERVER le stock
                $product->increment('reserved_quantity', $quantity);
                
                $subtotal += $product->price * $quantity;
            }

            // Créer la commande
            $order = Order::create([
                'user_id' => $user->id,
                'merchant_id' => $merchantId,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'customer_name' => $validated['shipping_address']['full_name'],
                'customer_email' => $user->email,
                'customer_phone' => $validated['shipping_address']['phone'],
                'shipping_address' => $validated['shipping_address']['street'] . ', ' . 
                                     $validated['shipping_address']['district'],
                'shipping_city' => $validated['shipping_address']['city'],
                'shipping_country' => 'Cameroun',
                'subtotal' => $subtotal,
                'shipping_cost' => $validated['delivery_cost'],
                'total_price' => $subtotal + $validated['delivery_cost'],
                'payment_method' => $validated['payment_method'], // ✅ Maintenant accepte les nouvelles valeurs
                'payment_status' => $validated['payment_method'] === 'cash_on_delivery' ? 'pending' : 'pending',
                'status' => 'pending',
            ]);

            // Créer les items
            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $product->price * $quantity,
                    'stock_deducted' => false,
                ]);
            }

            // Créer la conversation automatiquement
            Conversation::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'merchant_id' => $merchantId,
                    'customer_id' => $user->id,
                    'last_message_at' => now(),
                ]
            );

            $orders[] = $order->load('items.product.images', 'merchant');

            Log::info('✅ Commande créée', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'merchant_id' => $merchantId,
                'payment_method' => $validated['payment_method'],
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Commande(s) créée(s) avec succès',
            'data' => [
                'orders' => $orders,
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ Erreur création commande', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
}