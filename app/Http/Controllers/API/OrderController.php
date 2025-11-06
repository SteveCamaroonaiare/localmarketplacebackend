<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Liste des commandes (pour les clients)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $orders = Order::where('user_id', $user->id)
                ->with('items.product')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste commandes:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes'
            ], 500);
        }
    }

    /**
     * Détails d'une commande
     */
    public function show($id)
    {
        try {
            $order = Order::with(['items.product', 'merchant', 'user'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'customer_name' => 'required|string',
                'customer_email' => 'required|email',
                'customer_phone' => 'required|string',
                'shipping_address' => 'required|string',
                'shipping_city' => 'required|string',
                'payment_method' => 'required|in:cash,mobile_money,bank_transfer,card',
                'customer_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Calculer les totaux
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Vérifier le stock
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}"
                    ], 400);
                }

                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_description' => $product->description,
                    'product_image' => $product->image,
                    'product_sku' => $product->sku ?? null,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];

                // Déduire du stock
                $product->decrement('stock', $item['quantity']);
            }

            // Créer la commande
            $order = Order::create([
                'user_id' => $request->user()->id ?? null,
                'merchant_id' => $request->merchant_id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_country' => $request->shipping_country ?? 'Cameroun',
                'subtotal' => $subtotal,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'tax' => 0,
                'discount' => $request->discount ?? 0,
                'total_price' => $subtotal + ($request->shipping_cost ?? 0) - ($request->discount ?? 0),
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'customer_notes' => $request->customer_notes,
            ]);

            // Créer les items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $order->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création commande:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une commande (pour les vendeurs)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
                'tracking_number' => 'nullable|string',
                'merchant_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::findOrFail($id);
            
            // Vérifier que c'est le bon merchant
            $merchant = $request->user();
            if ($order->merchant_id !== $merchant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], 403);
            }

            // Mettre à jour selon le statut
            switch ($request->status) {
                case 'confirmed':
                    $order->markAsConfirmed();
                    break;
                case 'shipped':
                    $order->markAsShipped($request->tracking_number);
                    break;
                case 'delivered':
                    $order->markAsDelivered();
                    break;
                case 'cancelled':
                    // Restaurer le stock
                    foreach ($order->items as $item) {
                        if ($item->product) {
                            $item->product->increment('stock', $item->quantity);
                        }
                    }
                    $order->markAsCancelled();
                    break;
                default:
                    $order->update(['status' => $request->status]);
            }

            if ($request->has('merchant_notes')) {
                $order->update(['merchant_notes' => $request->merchant_notes]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour commande:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Annuler une commande (client)
     */
    public function cancel(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);
            
            // Vérifier que c'est le bon client
            $user = $request->user();
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], 403);
            }

            if (!$order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être annulée'
                ], 400);
            }

            // Restaurer le stock
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->markAsCancelled();

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    /**
     * Commandes d'un vendeur spécifique
     */
    public function merchantOrders(Request $request)
    {
        try {
            $merchant = $request->user();

            $status = $request->query('status'); // Filter par statut
            $query = Order::where('merchant_id', $merchant->id)
                ->with('items.product', 'user');

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur commandes merchant:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes'
            ], 500);
        }
    }

    /**
     * Statistiques des commandes (pour le dashboard)
     */
    public function stats(Request $request)
    {
        try {
            $merchant = $request->user();

            $stats = [
                'total_orders' => Order::where('merchant_id', $merchant->id)->count(),
                'pending_orders' => Order::where('merchant_id', $merchant->id)->where('status', 'pending')->count(),
                'delivered_orders' => Order::where('merchant_id', $merchant->id)->where('status', 'delivered')->count(),
                'total_revenue' => Order::where('merchant_id', $merchant->id)->where('payment_status', 'paid')->sum('total_price'),
                'today_orders' => Order::where('merchant_id', $merchant->id)->whereDate('created_at', today())->count(),
                'this_month_revenue' => Order::where('merchant_id', $merchant->id)
                    ->where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_price'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }
}