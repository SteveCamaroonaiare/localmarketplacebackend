<?php

namespace App\Http\Controllers\API;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\Order;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    // Récupérer toutes les conversations de l'utilisateur
    public function index(Request $request)
    {
        $user = $request->user();
        
        $conversations = Conversation::with([
                'customer:id,name,email,avatar',
                'merchant:id,name,logo,user_id',
                'product:id,name,image',
                'order:id,order_number',
                'latestMessage'
            ])
            ->where(function($query) use ($user) {
                // Si c'est un customer
                $query->where('customer_id', $user->id)
                      // Si c'est un merchant (via la relation merchant->user_id)
                      ->orWhereHas('merchant', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        // Ajouter le nombre de messages non lus pour chaque conversation
        foreach ($conversations as $conversation) {
            $conversation->unread_count = $conversation->unreadCountForUser($user->id);
        }

        return response()->json($conversations);
    }

    // Créer ou récupérer une conversation
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required_without:order_id|exists:products,id',
            'order_id' => 'required_without:product_id|exists:orders,id',
        ]);

        $user = $request->user();
        
        // Récupérer le merchant_id
        if ($request->product_id) {
            $product = Product::findOrFail($request->product_id);
            $merchantId = $product->merchant_id;
        } elseif ($request->order_id) {
            $order = Order::findOrFail($request->order_id);
            $merchantId = $order->merchant_id;
        } else {
            return response()->json(['message' => 'product_id ou order_id requis'], 400);
        }

        // Vérifier si une conversation existe déjà
        $conversation = Conversation::where('customer_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->when($request->product_id, function($query, $productId) {
                return $query->where('product_id', $productId);
            })
            ->when($request->order_id, function($query, $orderId) {
                return $query->where('order_id', $orderId);
            })
            ->first();

        // Si non, créer une nouvelle conversation
        if (!$conversation) {
            $conversation = Conversation::create([
                'product_id' => $request->product_id,
                'order_id' => $request->order_id,
                'customer_id' => $user->id,
                'merchant_id' => $merchantId,
                'last_message_at' => now(),
            ]);
        }

        return response()->json($conversation->load([
            'customer:id,name,email,avatar',
            'merchant:id,name,logo',
            'product:id,name,image',
            'order:id,order_number'
        ]), 201);
    }

    // Récupérer une conversation spécifique
    public function show(Conversation $conversation)
    {
        $user = auth()->user();
        
        // Vérifier l'autorisation
        if (!$conversation->canAccess($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Marquer les messages comme lus
        $conversation->markAsReadForUser($user->id);

        return response()->json($conversation->load([
            'customer:id,name,email,avatar',
            'merchant:id,name,logo,user_id',
            'product:id,name,image',
            'order:id,order_number,status',
            'messages.sender:id,name,email,avatar'
        ]));
    }

    // Récupérer les messages d'une conversation
    public function messages(Conversation $conversation)
    {
        $user = auth()->user();
        
        if (!$conversation->canAccess($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,email,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    // Envoyer un message
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = auth()->user();
        
        if (!$conversation->canAccess($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
            'attachment' => 'nullable|file|max:5120', // 5MB max
        ]);

        // Déterminer le type d'expéditeur
        $senderType = $user->id === $conversation->customer_id ? 'customer' : 'merchant';

        $messageData = [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => $senderType,
            'content' => $request->content,
        ];

        // Gérer l'upload de fichier si présent
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            
            $messageData['attachment_url'] = $path;
            $messageData['attachment_type'] = $file->getMimeType();
        }

        $message = Message::create($messageData);

        // Mettre à jour la conversation
        $conversation->update(['last_message_at' => now()]);

        // Diffuser l'événement
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message->load('sender'), 201);
    }

    // Marquer tous les messages comme lus
    public function markAsRead(Conversation $conversation)
    {
        $user = auth()->user();
        
        if (!$conversation->canAccess($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $conversation->markAsReadForUser($user->id);

        return response()->json(['success' => true]);
    }
}