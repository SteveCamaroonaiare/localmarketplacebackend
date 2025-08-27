<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'icon'];


    public function subCategories()
{
    return $this->hasMany(SubCategory::class);
}


    public function products()
    {
        return $this->hasMany(Product::class);
    }

   public function departments()
{
    return $this->belongsToMany(Department::class, 'department_category')
                ->withPivot('order')
                ->orderBy('department_category.order')
                ->withTimestamps();
} 
}