<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImageVariantSize extends Model
{
    protected $fillable = [
        'variant_id',
        'size_name',
        'stock_quantity',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductImageVariant::class, 'variant_id');
    }
}