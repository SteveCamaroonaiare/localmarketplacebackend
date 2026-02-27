<?php
// app/Http/Controllers/API/ProfileOrderController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class ProfileOrderController extends Controller
{
    /**
     * Récupérer toutes les commandes de l'utilisateur (version simplifiée)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $orders = Order::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number ?? 'CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at,
                        'items_count' => $order->items()->count(),
                        'conversation_id' => $order->conversation_id,
                    ];
                });

            return response()->json([
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du chargement des commandes'
            ], 500);
        }
    }
}