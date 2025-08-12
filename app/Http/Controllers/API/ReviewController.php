<?php

// app/Http/Controllers/ReviewController.php
namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $reviews = Review::with('user')
            ->where('product_id', $productId)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
          $user = Auth::user();
    
    // Vérifier si l'utilisateur a déjà commenté ce produit
    $existingReview = Review::where([
        'user_id' => $user->id,
        'product_id' => $request->product_id
    ])->exists();

    if ($existingReview) {
        return response()->json([
            'message' => 'Vous avez déjà commenté ce produit'
        ], 403);
    }
    
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review->load('user'), 201);
    }
}
