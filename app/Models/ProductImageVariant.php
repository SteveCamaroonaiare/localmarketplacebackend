<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImageVariant extends Model
{
    protected $fillable = [
        'product_id',
        'image_id',
        'color_name',
        'color_code',
        'price',
        'original_price',
        'stock_quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function image()
    {
        return $this->belongsTo(ProductImage::class, 'image_id');
    }

    public function sizes()
    {
        return $this->hasMany(ProductImageVariantSize::class, 'variant_id');
    }
}