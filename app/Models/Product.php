<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'original_price',
        'rating',
        'reviews',
        'seller',
        'location',
        'badge',
        'category_id',
        'sub_category_id',
        'stock_quantity',
        'restock_frequency',
        'return_policy',
        'payment_on_delivery'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

     public function images()
    {
        return $this->hasMany(VariantImage::class, 'product_id');
    }
    public function sizes()
    {
        return $this->hasMany(Size::class);
    }

    public function colorVariants()
    {
        return $this->hasMany(ColorVariant::class);
    }
    public function subCategory()
{
    return $this->belongsTo(SubCategory::class);
}
}