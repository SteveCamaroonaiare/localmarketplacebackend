<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorVariant extends Model
{
    protected $fillable = ['product_id', 'color_name', 'color_code', 'available'];

    public function images()
    {
        return $this->hasMany(VariantImage::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sizes()
    {
        return $this->hasMany(Size::class);
    }
}
