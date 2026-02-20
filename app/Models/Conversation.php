<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        
        'order_id',
        'customer_id',
        'merchant_id',
         'product_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Méthodes utiles
    public function canAccess($user)
    {
        return $this->customer_id === $user->id || 
               $this->merchant->user_id === $user->id;
    }

    public function unreadCountForUser($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsReadForUser($userId)
    {
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    // Accesseurs
    public function getOtherParticipantAttribute()
    {
        $currentUser = auth()->user();
        
        if ($this->customer_id === $currentUser->id) {
            return $this->merchant;
        }
        
        return $this->customer;
    }
}