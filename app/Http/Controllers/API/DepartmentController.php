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

    // Récupérer un département spécifique avec ses catégories
    public function show($slug)
    {
        $department = Department::where('slug', $slug)
                                ->with(['categories' => function($query) {
                                    $query->orderBy('department_category.order');
                                }])
                                ->first();

        if (!$department) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }

        return response()->json($department);
    }

    // Récupérer les produits d'un département
    public function products($slug)
    {
        $department = Department::where('slug', $slug)->first();

        if (!$department) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }

        // Récupérer les IDs des catégories du département
        $categoryIds = $department->categories->pluck('id');

        // Récupérer les produits de ces catégories
        $products = Product::with(['category', 'subCategory', 'colorVariants', 'images'])
                          ->whereIn('category_id', $categoryIds)
                          ->orderBy('created_at', 'desc')
                          ->paginate(20);

        return response()->json([
            'department' => $department,
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