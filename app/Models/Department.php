<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relation many-to-many avec Category
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'department_category') 
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('department_category.order', 'asc');  
    }

    /**
     * Récupérer tous les produits d'un département via ses catégories
     */
    public function products()
    {
        return Product::whereIn('category_id', $this->categories()->pluck('categories.id'));
    }

    /**
     * Scope pour les départements actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour trier par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}