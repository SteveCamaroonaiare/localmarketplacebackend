<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUpdate extends Model
{
    protected $fillable = [
        'product_id',
        'old_data',
        'new_data',
        'old_images',
        'new_images',
        'status',
        'rejection_reason'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'old_images' => 'array',
        'new_images' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
