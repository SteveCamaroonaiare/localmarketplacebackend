<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Merchant extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'logo',
        'shop_name',
        'description',
        'shop_address',
        'merchant_followers',
        'country',
        'category',
        'payment_method',
        'payment_account',
        'is_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
/**
 * 👥 Users qui suivent ce marchand
 */
public function followers()
{
    return $this->belongsToMany(
        User::class,
        'merchant_followers'
    )->withTimestamps();
}

/**
 * 📊 Nombre de followers
 */
public function getFollowersCountAttribute()
{
    return $this->followers()->count();
}

/**
 * 🔍 Vérifie si un user suit ce marchand
 */
public function isFollowedBy(?User $user): bool
{
    if (!$user) return false;

    return $this->followers()
        ->where('user_id', $user->id)
        ->exists();
}

public function conversations()
{
    return $this->hasMany(Conversation::class, 'merchant_id');
}

public function messages()
{
    return $this->hasManyThrough(Message::class, Conversation::class, 'merchant_id', 'conversation_id');
}

// Pour récupérer l'utilisateur associé
public function user()
{
    return $this->belongsTo(User::class);
}

// Helper pour récupérer les conversations d'un marchand
public function getConversations()
{
    return $this->conversations()
        ->with(['customer', 'product', 'latestMessage'])
        ->orderBy('last_message_at', 'desc')
        ->get();
}
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * 📦 Relation : un marchand reçoit plusieurs commandes
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 💰 Calcul du chiffre d’affaires total du marchand
     */
    public function getTotalRevenueAttribute()
    {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->sum('total_price');
    }

    /**
     * ⚙️ Statistiques rapides (utile pour le dashboard)
     */
    public function getStatsAttribute()
    {
        return [
            'total_orders' => $this->orders()->count(),
            'pending_orders' => $this->orders()->where('status', 'pending')->count(),
            'delivered_orders' => $this->orders()->where('status', 'delivered')->count(),
            'total_revenue' => $this->total_revenue,
            'total_products' => $this->products()->count(),
        ];
    }
}