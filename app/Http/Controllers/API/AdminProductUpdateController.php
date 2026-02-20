<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminProductUpdateController extends Controller
{
    public function pending()
{
    $updates = ProductUpdate::with('product')
        ->where('status', 'pending')
        ->latest()
        ->get();

    return response()->json([
        'success' => true,
        'updates' => $updates
    ]);
}
  public function approve($id)
{
    $update = ProductUpdate::findOrFail($id);
    $product = $update->product;

    // appliquer nouvelles données
    $product->update($update->new_data);

    // supprimer anciennes images
    foreach ($product->images as $image) {
        \Storage::disk('public')->delete($image->image_path);
        $image->delete();
    }

    // ajouter nouvelles images
    if ($update->new_images) {
        foreach ($update->new_images as $path) {
            $product->images()->create([
                'image_path' => str_replace('products/temp', 'products', $path)
            ]);

            \Storage::disk('public')->move(
                $path,
                str_replace('products/temp', 'products', $path)
            );
        }
    }

    $update->update(['status' => 'approved']);

    return response()->json([
        'success' => true
    ]);
}
public function reject(Request $request, $id)
{
    $update = ProductUpdate::findOrFail($id);

    $update->update([
        'status' => 'rejected',
        'rejection_reason' => $request->rejection_reason
    ]);

    return response()->json([
        'success' => true
    ]);
}

}
