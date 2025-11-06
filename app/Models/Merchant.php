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
        'shop_name',
        'shop_address',
        'country',
        'category',
        'payment_method',
        'payment_account',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    // Relationss
    public function user()
    {
        return $this->belongsTo(User::class);
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