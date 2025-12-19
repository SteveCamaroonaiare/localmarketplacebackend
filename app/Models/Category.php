<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
  protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'parent_id',
        'order',
        'active'
    ];

    public function subCategories()
{
    return $this->hasMany(SubCategory::class);
}


    public function products()
    {
    return $this->belongsToMany(Product::class, 'category_product');
    }

  public function departments()
{
    return $this->belongsToMany(Department::class, 'department_category')
        ->withPivot('order')
        ->withTimestamps();
}
}