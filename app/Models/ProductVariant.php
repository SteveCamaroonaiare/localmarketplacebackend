<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'color_name',
        'color_code',
        'size_name',
        'price',
        'original_price',
        'stock_quantity',
        'image_path',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(ProductVariantImage::class, 'variant_id');
    }

    // Générer un SKU unique
    public static function generateSKU($productId, $colorName = null, $sizeName = null)
    {
        $sku = 'PRD-' . str_pad($productId, 6, '0', STR_PAD_LEFT);
        
        if ($colorName) {
            $sku .= '-' . strtoupper(substr($colorName, 0, 3));
        }
        
        if ($sizeName) {
            $sku .= '-' . strtoupper($sizeName);
        }
        
        return $sku;
    }
}