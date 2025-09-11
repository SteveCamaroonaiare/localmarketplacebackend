<?php

namespace App\Http\Controllers\API;

use App\Models\Department;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DepartmentController extends Controller
{
    // Récupérer tous les départements
    public function index()
    {
        $departments = Department::where('active', true)
                                ->orderBy('order')
                                ->get();

        return response()->json($departments);
    }

public function show($slug)
    {
        $department = Department::with('categories')->where('slug', $slug)->firstOrFail();
        return response()->json($department);
    }

   public function products(Request $request, $slug)
{
    $department = Department::with('categories')->where('slug', $slug)->firstOrFail();
    
    // Récupérer les IDs des catégories liées à ce département
    $categoryIds = $department->categories->pluck('id');
    
    // Produits qui appartiennent à ces catégories
    $query = Product::whereHas('categories', function($q) use ($categoryIds) {
        $q->whereIn('categories.id', $categoryIds);
    });

    // Filtrage supplémentaire basé sur le département
    if ($slug === 'hommes') {
        $query->forMen();
    } elseif ($slug === 'femmes') {
        $query->forWomen();
    } elseif ($slug === 'enfants') {
        $query->forChildren();
    }

    if ($request->has('category_id')) {
        $query->where('category_id', $request->input('category_id'));
    }

    $products = $query->paginate(20);

    return response()->json([
        'products' => $products
    ]);
}
    

    // Récupérer toutes les catégories avec leurs départements
    public function categoriesWithDepartments()
    {
        $categories = Category::with('departments')
                             ->get()
                             ->map(function($category) {
                                 return [
                                     'id' => $category->id,
                                     'name' => $category->name,
                                     'departments' => $category->departments->pluck('slug')
                                 ];
                             });

        return response()->json($categories);
    }
}