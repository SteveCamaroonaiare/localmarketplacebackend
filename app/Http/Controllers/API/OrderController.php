<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Merchant;
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
            'customer_email' => 'nullable|email',
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
            $product = Product::with('images')->findOrFail($item['product_id']);
            
            if ($product->stock_quantity < $item['quantity']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuffisant pour {$product->name}. Stock disponible: {$product->stock_quantity}"
                ], 400);
            }

            $itemSubtotal = $product->price * $item['quantity'];
            $subtotal += $itemSubtotal;

            $imagePath = null;
            if ($product->images && $product->images->count() > 0) {
                $primaryImage = $product->images->where('is_primary', true)->first();
                $imagePath = $primaryImage 
                    ? $primaryImage->image_path
                    : $product->images->first()->image_path;
            }

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_description' => $product->description ?? '',
                'product_image' => $imagePath,
                'product_sku' => $product->sku ?? null,
                'unit_price' => $product->price,
                'quantity' => $item['quantity'],
                'subtotal' => $itemSubtotal,
                'attributes' => isset($item['attributes']) ? json_encode($item['attributes']) : null,
            ];

            $product->decrement('stock_quantity', $item['quantity']);
        }

        $shippingCost = $request->shipping_cost ?? 1000;
        $discount = $request->discount ?? 0;
        $total = $subtotal + $shippingCost - $discount;

        // Créer la commande
        $order = Order::create([
            'user_id' => $request->user() ? $request->user()->id : null,
            'merchant_id' => $request->merchant_id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email ?? 'noemail@example.com',
            'customer_phone' => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_country' => $request->shipping_country ?? 'Cameroun',
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax' => 0,
            'discount' => $discount,
            'total_price' => $total,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'customer_notes' => $request->customer_notes,
        ]);

        // Créer les items
        foreach ($orderItems as $item) {
            $order->items()->create($item);
        }

        // Charger les relations
        $order->load('items.product.images', 'merchant');

        // Récupérer le premier produit
        $firstProductId = $order->items->first()->product_id ?? null;

        // Créer la conversation
        $conversation = Conversation::create([
            'order_id' => $order->id,
            'customer_id' => $order->user_id,
            'merchant_id' => $order->merchant_id,
            'product_id' => $firstProductId,
            'last_message_at' => now(),
        ]);

        Log::info('✅ Conversation créée avec succès', [
            'conversation_id' => $conversation->id,
            'product_id' => $firstProductId,
        ]);

        // Message de bienvenue avec images
        $productsList = $order->items->map(function($item) {
            $attributes = '';
            if ($item->attributes) {
                $attrs = is_string($item->attributes) ? json_decode($item->attributes, true) : $item->attributes;
                if ($attrs) {
                    $attrStrings = [];
                    if (isset($attrs['color'])) $attrStrings[] = "Couleur: {$attrs['color']}";
                    if (isset($attrs['size'])) $attrStrings[] = "Taille: {$attrs['size']}";
                    if (!empty($attrStrings)) {
                        $attributes = ' (' . implode(', ', $attrStrings) . ')';
                    }
                }
            }
            return "• {$item->product_name}{$attributes} x{$item->quantity} = {$item->subtotal} FCFA";
        })->join("\n");

        // Récupérer les images
        $productImages = $order->items->map(function($item) {
            if ($item->product_image) {
                return url('storage/' . $item->product_image);
            }
            
            if ($item->product && $item->product->images && $item->product->images->count() > 0) {
                $primaryImage = $item->product->images->where('is_primary', true)->first();
                $imagePath = $primaryImage 
                    ? $primaryImage->image_path 
                    : $item->product->images->first()->image_path;
                return url('storage/' . $imagePath);
            }
            
            return null;
        })->filter()->unique()->values()->toArray();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $order->merchant_id,
            'sender_type' => 'merchant',
            'content' => "🎉 Bonjour {$order->customer_name} !\n\n" .
                         "Merci pour votre commande #{$order->order_number}.\n\n" .
                         "📦 Articles commandés :\n{$productsList}\n\n" .
                         "💰 Montant total : {$order->total_price} FCFA\n" .
                         "📍 Livraison : {$order->shipping_address}, {$order->shipping_city}\n\n" .
                         "Je confirme la disponibilité et reviens vers vous rapidement ! 😊",
            'is_read' => false,
            'attachments' => json_encode($productImages),
        ]);

        Log::info('✅ Message de bienvenue créé avec images', [
            'conversation_id' => $conversation->id,
            'images_count' => count($productImages),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => [
                'order' => $order,
                'conversation_id' => $conversation->id,
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('❌ Erreur création commande', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de la commande',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Mettre à jour le statut d'une commande (pour les vendeurs)
     */
   public function updateStatus(Request $request, $id)
{
    try {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'tracking_number' => 'nullable|string',
            'merchant_notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        
        // Vérifier que c'est bien une commande du merchant
        $order = Order::whereHas('merchant', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Mettre à jour le statut
        $order->status = $newStatus;
        
        if ($request->tracking_number) {
            $order->tracking_number = $request->tracking_number;
        }
        
        if ($request->merchant_notes) {
            $order->merchant_notes = $request->merchant_notes;
        }

        // ✅ Mettre à jour payment_status si la commande est confirmée
        if ($newStatus === 'confirmed' && $order->payment_status === 'pending') {
            $order->payment_status = 'paid';
        }

        // ✅ Si annulée, remettre le stock
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }
        }

        $order->save();

        // ✅ Envoyer un message automatique au client via la conversation
        $this->sendStatusUpdateMessage($order, $oldStatus, $newStatus);

        // ✅ Logger pour les stats admin (point 2)
        Log::info('📊 Changement statut commande', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'merchant_id' => $order->merchant_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'total_price' => $order->total_price,
            'payment_status' => $order->payment_status,
            'updated_by' => $user->id,
        ]);

        Log::info('✅ Statut commande mis à jour', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => $order->fresh()->load('items.product', 'merchant')
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Erreur mise à jour statut', [
            'error' => $e->getMessage(),
            'order_id' => $id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du statut'
        ], 500);
    }
}

/**
 * Envoyer un message automatique au client lors du changement de statut
 */
private function sendStatusUpdateMessage($order, $oldStatus, $newStatus)
{
    try {
        // Trouver la conversation
        $conversation = Conversation::where('order_id', $order->id)->first();
        
        if (!$conversation) {
            return;
        }

        // Messages selon le statut
        $messages = [
            'confirmed' => "✅ Bonne nouvelle ! Votre commande #{$order->order_number} a été confirmée.\n\n" .
                          "Nous préparons vos articles avec soin. Vous serez notifié dès l'expédition.",
            
            'processing' => "📦 Votre commande #{$order->order_number} est en cours de préparation.\n\n" .
                           "Nos équipes s'affairent à préparer vos articles.",
            
            'shipped' => "🚚 Votre commande #{$order->order_number} a été expédiée !\n\n" .
                        ($order->tracking_number ? "Numéro de suivi : {$order->tracking_number}\n\n" : "") .
                        "Vous devriez la recevoir sous peu.",
            
            'delivered' => "🎉 Votre commande #{$order->order_number} a été livrée !\n\n" .
                          "Merci pour votre confiance. N'hésitez pas à nous laisser un avis.",
            
            'cancelled' => "❌ Votre commande #{$order->order_number} a été annulée.\n\n" .
                          "Si vous avez des questions, n'hésitez pas à nous contacter.",
        ];

        $content = $messages[$newStatus] ?? null;

        if ($content) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => auth()->id(),
                'sender_type' => 'merchant',
                'content' => $content,
                'is_read' => false,
            ]);

            $conversation->update([
                'last_message_at' => now()
            ]);
        }

    } catch (\Exception $e) {
        Log::error('❌ Erreur envoi message statut', [
            'error' => $e->getMessage()
        ]);
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
        $user = auth()->user();
        
        Log::info('👤 Utilisateur authentifié', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        // ✅ Récupérer le merchant via user_id
        $merchant = Merchant::where('user_id', $user->id)->first();
        
        if (!$merchant) {
            Log::warning('⚠️ Merchant non trouvé pour cet utilisateur', [
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être un vendeur pour accéder à cette page'
            ], 403);
        }

        Log::info('🏪 Merchant trouvé', [
            'merchant_id' => $merchant->id,
            'merchant_name' => $merchant->name,
        ]);

        $query = Order::with(['items.product', 'merchant'])
            ->where('merchant_id', $merchant->id);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // Ajouter le compteur de messages non lus
        foreach ($orders as $order) {
            $conversation = Conversation::where('order_id', $order->id)->first();
            
            $order->unread_messages_count = $conversation 
                ? $conversation->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count()
                : 0;
        }

        Log::info('✅ Commandes récupérées', [
            'count' => $orders->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Erreur commandes merchant', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des commandes',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    /**
     * Statistiques des commandes (pour le dashboard)
     */
    public function stats(Request $request)
{
    try {
        $user = auth()->user();
        
        $merchant = Merchant::where('user_id', $user->id)->first();
        
        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant non trouvé'
            ], 404);
        }

        // ✅ Statistiques complètes
        $totalOrders = Order::where('merchant_id', $merchant->id)->count();
        
        $pendingOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'pending')
            ->count();
        
        $confirmedOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'confirmed')
            ->count();
        
        $processingOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'processing')
            ->count();
        
        $shippedOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'shipped')
            ->count();
        
        $deliveredOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'delivered')
            ->count();
        
        $cancelledOrders = Order::where('merchant_id', $merchant->id)
            ->where('status', 'cancelled')
            ->count();

        // ✅ Revenus totaux (seulement commandes payées/confirmées/livrées)
        $totalRevenue = Order::where('merchant_id', $merchant->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereIn('payment_status', ['paid', 'pending']) // On compte même si paiement en attente
            ->sum('total_price');

        // ✅ Revenus de ce mois
        $thisMonthRevenue = Order::where('merchant_id', $merchant->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_price');

        // ✅ Revenus d'aujourd'hui
        $todayRevenue = Order::where('merchant_id', $merchant->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereDate('created_at', now()->toDateString())
            ->sum('total_price');

        // ✅ Commandes d'aujourd'hui
        $todayOrders = Order::where('merchant_id', $merchant->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'confirmed_orders' => $confirmedOrders,
                'processing_orders' => $processingOrders,
                'shipped_orders' => $shippedOrders,
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_revenue' => $totalRevenue,
                'this_month_revenue' => $thisMonthRevenue,
                'today_revenue' => $todayRevenue,
                'today_orders' => $todayOrders,
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Erreur stats merchant', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des statistiques'
        ], 500);
    }
}

    public function merchantOrderDetails($id)
    {
        try {
            $user = auth()->user();
            
            $order = Order::with([
                'items.product.images',
                'merchant',
                'user' // Le client
            ])
            ->whereHas('merchant', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($id);

            // Charger la conversation associée
            $conversation = Conversation::with(['messages.sender'])
                ->where('order_id', $order->id)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'conversation' => $conversation,
                    'unread_messages' => $conversation 
                        ? $conversation->messages()->where('sender_id', '!=', $user->id)->where('is_read', false)->count()
                        : 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur détails commande merchant', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails'
            ], 500);
        }
    }

    /**
     * Récupérer la conversation d'une commande
     */
    public function getOrderConversation($orderId)
    {
        try {
            $user = auth()->user();
            
            $order = Order::whereHas('merchant', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->findOrFail($orderId);

            $conversation = Conversation::with([
                'messages.sender:id,name,email',
                'customer:id,name,email,avatar',
                'order:id,order_number,customer_name,customer_phone,shipping_address,shipping_city',
                'order.items'
            ])
            ->where('order_id', $order->id)
            ->firstOrFail();

            // Marquer les messages du client comme lus
            $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur conversation commande', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable'
            ], 404);
        }
    }

    /**
     * Envoyer un message au client depuis le dashboard
     */
    public function sendMessageToCustomer(Request $request, $orderId)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $user = auth()->user();
            
            // Vérifier que c'est bien une commande du merchant
            $order = Order::whereHas('merchant', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->findOrFail($orderId);

            // Récupérer ou créer la conversation
            $conversation = Conversation::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_id' => $order->user_id,
                    'merchant_id' => $order->merchant_id,
                    'product_id' => $order->items->first()->product_id ?? null,
                    'last_message_at' => now(),
                ]
            );

            // Créer le message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'sender_type' => 'merchant',
                'content' => trim($request->content),
                'is_read' => false,
            ]);

            // Mettre à jour la conversation
            $conversation->update([
                'last_message_at' => now()
            ]);

            $message->load('sender:id,name,email');

            Log::info('✅ Message merchant envoyé', [
                'message_id' => $message->id,
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi message merchant', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message'
            ], 500);
        }
    }


    
}