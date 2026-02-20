<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    // Récupérer les messages d'une conversation
    public function index(Conversation $conversation)
    {
        try {
            $user = auth()->user();
            
            // Vérifier l'accès
            $hasAccess = $conversation->customer_id === $user->id || 
                        ($conversation->merchant && $conversation->merchant->user_id === $user->id);
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $messages = $conversation->messages()
                ->with('sender:id,name')
                ->orderBy('created_at', 'asc')
                ->get();

            // Marquer les messages comme lus
            $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération messages', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages'
            ], 500);
        }
    }

    // Envoyer un message
    public function store(Request $request, Conversation $conversation)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $user = auth()->user();
            
            // Vérifier l'accès
            $hasAccess = $conversation->customer_id === $user->id || 
                        ($conversation->merchant && $conversation->merchant->user_id === $user->id);
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Déterminer le sender_type
            $senderType = 'customer';
            
            if ($conversation->merchant && $conversation->merchant->user_id === $user->id) {
                $senderType = 'merchant';
            }

            Log::info('📤 Création message', [
                'user_id' => $user->id,
                'sender_type' => $senderType,
                'conversation_id' => $conversation->id
            ]);

            // Créer le message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'sender_type' => $senderType,
                'content' => $request->content,
                'is_read' => false,
            ]);

            // Mettre à jour la conversation
            $conversation->update([
                'last_message_at' => now()
            ]);

            Log::info('✅ Message créé', [
                'message_id' => $message->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $message->load('sender:id,name')
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}