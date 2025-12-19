<?php
// Dans app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'merchant_id', 'order_number', 'status', 'customer_name',
        'customer_email', 'customer_phone', 'shipping_address', 'shipping_city',
        'shipping_country', 'subtotal', 'shipping_cost', 'tax', 'discount',
        'total_price', 'payment_method', 'payment_status', 'transaction_id',
        'customer_notes', 'merchant_notes', 'tracking_number', 'paid_at',
        'shipped_at', 'delivered_at', 'cancelled_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Générer un numéro de commande unique
    public static function generateOrderNumber()
    {
        $prefix = 'ORD-';
        $date = now()->format('Ymd');
        $lastOrder = self::where('order_number', 'like', $prefix . $date . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }



    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Méthodes pour changer le statut
    public function markAsConfirmed()
    {
        $this->update([
            'status' => 'confirmed',
            'payment_status' => 'paid' // Si le paiement est confirmé
        ]);
    }

    public function markAsShipped($trackingNumber = null)
    {
        $this->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'shipped_at' => now()
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    // Accessor pour le badge de statut
    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => ['text' => 'En attente', 'color' => 'warning'],
            'confirmed' => ['text' => 'Confirmée', 'color' => 'info'],
            'processing' => ['text' => 'En traitement', 'color' => 'primary'],
            'shipped' => ['text' => 'Expédiée', 'color' => 'secondary'],
            'delivered' => ['text' => 'Livrée', 'color' => 'success'],
            'cancelled' => ['text' => 'Annulée', 'color' => 'danger'],
            'refunded' => ['text' => 'Remboursée', 'color' => 'dark'],
        ];

        return $statuses[$this->status] ?? ['text' => 'Inconnu', 'color' => 'secondary'];
    }







    public function conversation()
{
    return $this->hasOne(Conversation::class);
}

public function canStartConversation()
{
    // Une conversation peut être démarrée si la commande existe et n'est pas annulée
    return $this->exists && $this->status !== 'cancelled';
}
}