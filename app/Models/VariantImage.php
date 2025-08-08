<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantImage extends Model
{
    protected $fillable = ['product_id','color_variant_id', 'image_url', 'is_main'];

    public function colorVariant()
    {
        return $this->belongsTo(ColorVariant::class);
    }
    public function imageable()
    {
        return $this->morphTo();
    }
}
