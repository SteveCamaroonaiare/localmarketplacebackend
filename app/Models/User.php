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
        'google_id',
        'role',
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
    return $this->belongsToMany(
        Merchant::class,
        'merchant_followers'
    )->withTimestamps();
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

    // Helper pour récupérer l'utilisateur marchand associé
    public function isMerchant()
    {
        return $this->merchant()->exists();
    }

    public function getMerchantId()
    {
        return $this->merchant ? $this->merchant->id : null;
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
    public function isSuperAdmin()
    {
        return $this->admin_role === 'super_admin' && $this->is_active_admin;
    }

    public function isAdmin()
    {
        return !is_null($this->admin_role) && $this->is_active_admin;
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
}