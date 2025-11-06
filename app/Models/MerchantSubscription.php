<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id', 'plan_id', 'stripe_subscription_id', 'status',
        'billing_cycle', 'amount', 'starts_at', 'ends_at', 'trial_ends_at', 'canceled_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function isOnTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at > now();
    }

    public function daysUntilExpiration()
    {
        return now()->diffInDays($this->ends_at);
    }

    public function renew()
    {
        $this->update([
            'ends_at' => $this->billing_cycle === 'monthly' 
                ? now()->addMonth() 
                : now()->addYear()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now()
        ]);
    }
}