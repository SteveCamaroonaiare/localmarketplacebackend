<?php
namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()->with('sender')->latest()->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => Auth::id(),
            'content' => $request->content,
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message->load('sender'), 201);
    }

    public function markAsRead(Message $message)
    {
        $this->authorize('update', $message->conversation);

        if (is_null($message->read_at)) {
            $message->update(['read_at' => now()]);
        }

        return response()->json($message);
    }

    // Récupérer le nombre de messages non lus
    public function unreadCount()
    {
        $user = Auth::user();
        
        $unreadCount = Message::whereHas('conversation.participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('sender_id', '!=', $user->id)
        ->whereNull('read_at')
        ->count();

        return response()->json(['count' => $unreadCount]);
    }
}