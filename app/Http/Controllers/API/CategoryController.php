<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    // Récupérer toutes les catégories
    public function index()
    {
        $categories = Category::withCount('products')->get();
        return response()->json($categories);
    }
}