<?php
// app/Http\Controllers/API/AdminManagementController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminManagementController extends Controller
{
    /**
     * Lister tous les administrateurs
     */
    public function index()
    {
        try {
            $admins = User::admins()
                ->with('approvedBy')
                ->get()
                ->map(function ($admin) {
                    return [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'admin_role' => $admin->admin_role,
                        'is_active_admin' => $admin->is_active_admin,
                        'admin_since' => $admin->admin_since,
                        'approved_by' => $admin->approvedBy?->name,
                        'role' => $admin->role,
                    ];
                });

            return response()->json([
                'success' => true,
                'admins' => $admins
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des administrateurs'
            ], 500);
        }
    }

    /**
     * Créer un nouvel administrateur
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'admin_role' => 'required|in:admin,super_admin',
            ]);

            $superAdmin = $request->user();

            $admin = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'admin_role' => $request->admin_role,
                'is_active_admin' => true,
                'admin_since' => now(),
                'approved_by' => $superAdmin->id,
                'role' => 'client', // Rôle de base
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Administrateur créé avec succès',
                'admin' => $admin
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'administrateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Promouvoir un utilisateur existant en administrateur
     */
    public function promoteUser(Request $request, $userId)
    {
        try {
            $request->validate([
                'admin_role' => 'required|in:admin,super_admin',
            ]);

            $superAdmin = $request->user();
            $user = User::findOrFail($userId);

            // Vérifier si l'utilisateur n'est pas déjà admin
            if ($user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà administrateur'
                ], 400);
            }

            $user->update([
                'admin_role' => $request->admin_role,
                'is_active_admin' => true,
                'admin_since' => now(),
                'approved_by' => $superAdmin->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur promu administrateur avec succès',
                'admin' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la promotion de l\'utilisateur'
            ], 500);
        }
    }

    /**
     * Désactiver un administrateur
     */
    public function deactivateAdmin($adminId)
    {
        try {
            $admin = User::whereNotNull('admin_role')->findOrFail($adminId);

            $admin->update([
                'is_active_admin' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Administrateur désactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation de l\'administrateur'
            ], 500);
        }
    }

    /**
     * Réactiver un administrateur
     */
    public function activateAdmin($adminId)
    {
        try {
            $admin = User::whereNotNull('admin_role')->findOrFail($adminId);

            $admin->update([
                'is_active_admin' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Administrateur réactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réactivation de l\'administrateur'
            ], 500);
        }
    }

    /**
     * Statistiques des administrateurs
     */
    public function stats()
    {
        try {
            $stats = [
                'total_admins' => User::admins()->count(),
                'super_admins' => User::superAdmins()->count(),
                'regular_admins' => User::regularAdmins()->count(),
                'inactive_admins' => User::whereNotNull('admin_role')->where('is_active_admin', false)->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
      
    public function ordersDashboard()
    {
        try {
            // Commandes
            $totalOrders = Order::count();
            $pendingOrders = Order::where('status', 'pending')->count();
            $confirmedOrders = Order::where('status', 'confirmed')->count();
            $shippedOrders = Order::where('status', 'shipped')->count();
            $deliveredOrders = Order::where('status', 'delivered')->count();
            $cancelledOrders = Order::where('status', 'cancelled')->count();
            
            // Revenus
            $totalRevenue = Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->sum('total_price');
            
            $thisMonthRevenue = Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_price');

            // Commissions (3%)
            $commissionRate = 0.03;
            $totalCommission = $totalRevenue * $commissionRate;
            $thisMonthCommission = $thisMonthRevenue * $commissionRate;

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => [
                        'total' => $totalOrders,
                        'pending' => $pendingOrders,
                        'confirmed' => $confirmedOrders,
                        'shipped' => $shippedOrders,
                        'delivered' => $deliveredOrders,
                        'cancelled' => $cancelledOrders,
                    ],
                    'revenue' => [
                        'total' => $totalRevenue,
                        'this_month' => $thisMonthRevenue,
                    ],
                    'commissions' => [
                        'rate' => $commissionRate,
                        'total' => $totalCommission,
                        'this_month' => $thisMonthCommission,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dashboard commandes admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Liste de toutes les commandes (admin)
     */
    public function getAllOrders(Request $request)
    {
        try {
            $query = Order::with([
                'items.product',
                'merchant:id,name,shop_name,email,phone',
                'user:id,name,email'
            ]);

            // Filtrer par statut
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Recherche
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            // Calculer les commissions
            $commissionRate = 0.03;
            foreach ($orders as $order) {
                $order->commission = $order->total_price * $commissionRate;
                
                // Messages non lus
                $conversation = Conversation::where('order_id', $order->id)->first();
                $order->unread_messages = $conversation 
                    ? $conversation->messages()->where('is_read', false)->count()
                    : 0;
            }

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur commandes admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Détails d'une commande
     */
    public function getOrderDetails($id)
    {
        try {
            $order = Order::with([
                'items.product.images',
                'merchant:id,name,shop_name,email,phone',
                'user:id,name,email'
            ])->findOrFail($id);

            // Conversation
            $conversation = Conversation::with(['messages.sender'])
                ->where('order_id', $order->id)
                ->first();

            // Commission
            $order->commission = $order->total_price * 0.03;

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'conversation' => $conversation,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur détails commande admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }
    }
}