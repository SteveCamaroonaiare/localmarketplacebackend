<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'color_variant_id',
        'product_name',
        'price',
        'original_price',
        'image',
        'size',
        'color',
        'seller',
        'location',
        'quantity'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function colorVariant()
    {
        return $this->belongsTo(ColorVariant::class);
    }
}