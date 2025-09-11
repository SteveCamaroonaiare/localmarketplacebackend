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
        'sexe',
        'age_group',
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
public function categories()
{
    return $this->belongsToMany(Category::class, 'category_product');
}
// Scope pour faciliter les requêtes
public function scopeForMen($query)
{
    return $query->where('sexe', 'H')->where('age_group', 'adult');
}

public function scopeForWomen($query)
{
    return $query->where('sexe', 'F')->where('age_group', 'adult');
}

public function scopeForChildren($query)
{
    return $query->where('age_group', 'child');
}

public function scopeForAdults($query)
{
    return $query->where('age_group', 'adult');
}

}