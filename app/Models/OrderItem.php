<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id',
        'product_name', 'product_description', 'product_image', 'product_sku',
        'unit_price', 'quantity', 'subtotal', 'attributes'
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    // 🔗 Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
