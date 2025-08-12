<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = [
        'product_id',
        'color_variant_id',
        'name',
        'price',
        'available',
        'stock_quantity',
        'restock_frequency',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function colorVariant()
    {
        return $this->belongsTo(ColorVariant::class);
    }
}
