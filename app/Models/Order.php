<?php
// Dans app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
     'user_id',
        'merchant_id',
        'order_number',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_country',
        'delivery_type',
        'delivery_status',
        'delivery_person',
        'delivery_notes',
        'subtotal',
        'shipping_cost',
        'tax',
        'discount',
        'total_price',
        'payment_method',
        'payment_status',
        'transaction_id',
        'paid_at',
        'customer_notes',
        'merchant_notes',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
    'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Générer un numéro de commande unique
   
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

    // ✅ AJOUTEZ CETTE MÉTHODE : Générer un numéro de commande
    public static function generateOrderNumber()
    {
        $year = date('Y');
        $lastOrder = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastOrder ? intval(substr($lastOrder->order_number, -5)) + 1 : 1;
        
        return 'ORD-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    // ✅ AJOUTEZ : URL WhatsApp pour le merchant
   public function getWhatsappUrlAttribute()
{
    $phone = $this->merchant->phone; // Utilisez $this au lieu de $order
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (!str_starts_with($phone, '237')) {
        $phone = '237' . $phone;
    }

    $itemsList = $this->items->map(function ($item) {
    $attributes = '';

    if ($item->attributes) {
        $attrs = is_array($item->attributes)
            ? $item->attributes
            : json_decode($item->attributes, true);

        $attrParts = [];
        if (!empty($attrs['color'])) $attrParts[] = "Couleur: {$attrs['color']}";
        if (!empty($attrs['size'])) $attrParts[] = "Taille: {$attrs['size']}";

        if ($attrParts) {
            $attributes = ' (' . implode(', ', $attrParts) . ')';
        }
    }

    return "• {$item->product_name}{$attributes} x{$item->quantity} = {$item->subtotal} FCFA";
})->join("\n");
  
    $imagesText = $this->items->pluck('image_url')->filter()->map(function($url) {
        return "📷 {$url}";
    })->join("\n");
    $message =
    "🛒 *Nouvelle commande #{$this->order_number}*\n\n" .
    "👤 Client: {$this->customer_name}\n" .
    "📞 Tél: {$this->customer_phone}\n" .
    "📍 Adresse: {$this->shipping_address}, {$this->shipping_city}\n\n" .
    "📦 *Articles:*\n{$itemsList}\n\n" .
    $imagesText . "\n\n" .
    "💰 *Total: {$this->total_price} FCFA*\n" .
    "💳 Mode de paiement: {$this->payment_method}\n\n" .
    "Merci de confirmer la commande !";

return "https://wa.me/{$phone}?text=" . urlencode($message);

}

public function canStartConversation()
{
    // Une conversation peut être démarrée si la commande existe et n'est pas annulée
    return $this->exists && $this->status !== 'cancelled';
}
}