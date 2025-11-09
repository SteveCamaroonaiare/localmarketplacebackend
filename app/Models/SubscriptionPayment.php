<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'method',
        'transaction_reference',
        'payment_details',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime'
    ];

    // Relations
    public function subscription()
    {
        return $this->belongsTo(MerchantSubscription::class, 'subscription_id');
    }

    public function merchant()
    {
        return $this->hasOneThrough(
            Merchant::class,
            MerchantSubscription::class,
            'id', // Foreign key on MerchantSubscription table
            'id', // Foreign key on Merchant table
            'subscription_id', // Local key on SubscriptionPayment table
            'merchant_id' // Local key on MerchantSubscription table
        );
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Methods
    public function markAsPaid($transactionReference = null)
    {
        $this->update([
            'status' => 'paid',
            'transaction_reference' => $transactionReference,
            'paid_at' => now()
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => 'failed'
        ]);
    }
}