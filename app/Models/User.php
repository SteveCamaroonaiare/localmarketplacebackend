<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'wallet_balance',
        'is_customer',
         'is_merchant',
         'shop_name', 'shop_address',
         'country', 
         'shop_logo', 'shop_category',
        'payment_method', 'payment_account', 'merchant_status',
        'google_id',
        'admin_role', 
        'is_active_admin',
        'admin_since',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_customer' => 'boolean',
        'is_merchant' => 'boolean',
        'email_verified_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
        'is_active_admin' => 'boolean',
        'admin_since' => 'datetime',

    ];
/**
 * 🏪 Marchands suivis par l'utilisateur
 */
public function followedMerchants()
{
    return $this->belongsToMany(User::class, 'merchant_followers', 'user_id', 'merchant_id')
        ->wherePivot('user_id', $this->id)
        ->where('is_merchant', true);
    
}

    // ========== RELATIONS EXISTANTES ==========
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function merchant()
    {
        return $this->hasOne(Merchant::class);
    }
    
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedAdmins()
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    // ========== NOUVELLES RELATIONS MESSAGERIE ==========
    public function conversationsAsCustomer()
    {
        return $this->hasMany(Conversation::class, 'customer_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversations()
    {
        // Récupérer toutes les conversations où l'utilisateur est soit customer, soit merchant
        return Conversation::where('customer_id', $this->id)
            ->orWhereHas('merchant', function($query) {
                $query->where('user_id', $this->id);
            });
    }

     public function isCustomer(): bool
    {
        return $this->is_customer;
    }
    
    public function isMerchant(): bool
    {
        return $this->is_merchant && $this->merchant()->exists();
    }
    
    public function canBeMerchant(): bool
    {
        return $this->is_merchant;
    }

    public function getMerchantId()
    {
        return $this->merchant ? $this->merchant->id : null;
    }
/**
     * Obtenir le rôle actif (selon la session ou défaut)
     */
    public function getActiveRole(): string
    {
        if ($this->isMerchant() && session('active_role') === 'merchant') {
            return 'merchant';
        }
        return 'customer';
    }

    /**
     * Obtenir le profil selon le rôle actif
     */
    public function getActiveProfile()
    {
        if ($this->getActiveRole() === 'merchant') {
            return $this->merchant;
        }
        return $this;
    }
    // ========== SCOPES ==========
    public function scopeAdmins($query)
    {
        return $query->whereNotNull('admin_role')->where('is_active_admin', true);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('admin_role', 'super_admin')->where('is_active_admin', true);
    }

    public function scopeRegularAdmins($query)
    {
        return $query->where('admin_role', 'admin')->where('is_active_admin', true);
    }

    // ========== MÉTHODES HELPERS ==========
 // app/Models/User.php
public function isAdmin()
{
    return $this->admin_role === 'admin' || $this->admin_role === 'super_admin';
}

public function isSuperAdmin()
{
    return $this->admin_role === 'super_admin';
}

    public function canManageAdmins()
    {
        return $this->isSuperAdmin();
    }

    public function canApproveProducts()
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    // Pour l'avatar
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }
    public function hasShop(): bool
    {
        return !is_null($this->shop_name);
    }
}