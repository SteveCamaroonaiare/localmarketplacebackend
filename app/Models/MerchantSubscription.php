<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MerchantSubscription extends Model
{
    protected $fillable = [
        'merchant_id',
        'plan_id',
        'billing_cycle',
        'amount',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = ['is_active', 'days_remaining'];

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

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->ends_at) {
            return max(0, Carbon::now()->diffInDays($this->ends_at, false));
        }
        return 0;
    }
}