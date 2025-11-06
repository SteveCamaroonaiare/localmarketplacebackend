<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'monthly_price', 'yearly_price',
        'product_limit', 'order_limit', 'commission_rate', 'features',
        'is_active', 'is_popular', 'sort_order'
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(MerchantSubscription::class, 'plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    // Accessors
    public function getYearlyDiscountAttribute()
    {
        if ($this->monthly_price > 0 && $this->yearly_price > 0) {
            $yearlyFromMonthly = $this->monthly_price * 12;
            return round((($yearlyFromMonthly - $this->yearly_price) / $yearlyFromMonthly) * 100);
        }
        return 0;
    }
}