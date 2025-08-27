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

    public function products()
    {
        return $this->hasManyThrough(Product::class, Category::class);
    }
}