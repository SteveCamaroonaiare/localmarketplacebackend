<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'slug', 'order', 'active'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'department_category')
                    ->withPivot('order')
                    ->orderBy('department_category.order')
                    ->withTimestamps();
    }

    // Produits via les catégories du département
public function products()
{
    return $this->belongsToMany(Product::class, 'department_category', 'department_id', 'category_id')
        ->withPivot('order')
        ->orderBy('department_category.order');
}
}