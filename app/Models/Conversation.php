<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'order_id',
        'customer_id',
        'merchant_id',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime'
    ];

    // ========== RELATIONS ==========
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    // ========== MÉTHODES UTILES ==========
    public function otherParticipant($userId)
    {
        // Si l'utilisateur est le customer, retourner le merchant
        if ($this->customer_id == $userId) {
            return $this->merchant;
        }
        
        // Sinon, vérifier si l'utilisateur est le marchand via la relation merchant
        if ($this->merchant && $this->merchant->user_id == $userId) {
            return $this->customer;
        }
        
        return null;
    }

    public function getOtherUserAttribute()
    {
        $user = auth()->user();
        if (!$user) return null;
        
        return $this->otherParticipant($user->id);
    }

    public function markAsReadForUser($userId)
    {
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    public function unreadCountForUser($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    // ========== SCOPES ==========
    public function scopeForCustomer($query, $userId)
    {
        return $query->where('customer_id', $userId);
    }

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function scopeForUser($query, $userId)
    {
        // Pour un utilisateur qui peut être soit customer, soit merchant
        return $query->where(function($q) use ($userId) {
            $q->where('customer_id', $userId)
              ->orWhereHas('merchant', function($q2) use ($userId) {
                  $q2->where('user_id', $userId);
              });
        });
    }

    public function canAccess($user)
{
    return $user->id === $this->customer_id 
        || ($this->merchant && $this->merchant->user_id === $user->id);
}

}