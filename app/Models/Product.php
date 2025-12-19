<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'price',
        'original_price',
        'stock_quantity',
        'category_id',
        'sub_category_id',
        'department_id',
        'status',
        'rejection_reason',
        'validated_at',
        'validated_by',
        'rating',
        'reviews_count',
        'is_featured',
        'payment_on_delivery',  // ⚠️ Vérifier si c'est 'payment_on_delivery' ou 'payment_on_delivery'
        'return_policy',
        'sku',
        'images',
        'is_active',
'reviews',
        'appoved_by',
        'approved_at',
        // N'ajoutez que les colonnes qui existent vraiment dans votre table !
        // Si ces colonnes n'existent pas, commentez-les ou supprimez-les :
        // 'sexe',
        // 'age_group',
        // 'restock_frequency',
        // 'seller',
        // 'location',
        // 'badge',
        // 'reviews', // ou 'reviews_count' ?
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'validated_at' => 'datetime',
        'payment_on_delivery' => 'boolean',
        'return_policy' => 'boolean', // ⚠️ C'est un boolean dans votre table
        'has_color_variants' => 'boolean',
        'rating' => 'decimal:1',
        'reviews' => 'integer',
        'is_active' => 'boolean',
        
    ];

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function colorVariants()
    {
        return $this->hasMany(ColorVariant::class);
    }

    public function sizes()
    {
        return $this->hasMany(Size::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }


public function department()
{
    return $this->belongsTo(Department::class);
}







    

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    // Accessors
    public function getDiscountPercentageAttribute()
    {
        if ($this->original_price && $this->original_price > $this->price) {
            return round((($this->original_price - $this->price) / $this->original_price) * 100);
        }
        return 0;
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => ['text' => 'En attente', 'color' => 'warning'],
            'approved' => ['text' => 'Approuvé', 'color' => 'success'],
            'rejected' => ['text' => 'Refusé', 'color' => 'danger'],
        ];
        return $statuses[$this->status] ?? ['text' => 'Inconnu', 'color' => 'secondary'];
    }
    public function scopeVisible($query)
    {
        return $query->where('status', 'approved')->where('is_active', true);
    }
}