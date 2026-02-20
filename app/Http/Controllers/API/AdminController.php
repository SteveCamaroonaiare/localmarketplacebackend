<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Product;
use App\Models\Conversation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    /**
     * Dashboard - Vue d'ensemble complète
     */
    public function dashboard()
    {
        try {
            // ✅ Statistiques générales
            $totalUsers = User::count();
            $totalMerchants = Merchant::count();
            $totalOrders = Order::count();
            $totalProducts = Product::count();

            // Nouveaux ce mois
            $newUsersThisMonth = User::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
            
            $newMerchantsThisMonth = Merchant::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            // Merchants en attente
            $pendingMerchants = Merchant::where('is_verified', false)
                ->orWhere('is_verified', null)
                ->count();

            // Produits en attente
            $pendingProducts = Product::where('status', 'pending')->count();

            // Commandes
            $pendingOrders = Order::where('status', 'pending')->count();
            $deliveredOrders = Order::where('status', 'delivered')->count();

            // Revenus
            $totalRevenue = Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->sum('total_price');
            
            $thisMonthRevenue = Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_price');

            // Commissions (3%)
            $totalCommission = $totalRevenue * 0.03;
            $thisMonthCommission = $thisMonthRevenue * 0.03;

            // Taux d'approbation merchants
            $approvedMerchants = Merchant::where('is_verified', true)->count();
            $approvalRate = $totalMerchants > 0 
                ? round(($approvedMerchants / $totalMerchants) * 100, 1) 
                : 0;

            // Croissance
            $lastMonthUsers = User::whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->count();
            $userGrowth = $lastMonthUsers > 0 
                ? round((($newUsersThisMonth - $lastMonthUsers) / $lastMonthUsers) * 100, 1)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_users' => $totalUsers,
                        'total_merchants' => $totalMerchants,
                        'total_orders' => $totalOrders,
                        'total_products' => $totalProducts,
                        'new_users_this_month' => $newUsersThisMonth,
                        'new_merchants_this_month' => $newMerchantsThisMonth,
                        'pending_merchants' => $pendingMerchants,
                        'pending_products' => $pendingProducts,
                        'pending_orders' => $pendingOrders,
                        'delivered_orders' => $deliveredOrders,
                        'user_growth' => $userGrowth,
                        'approval_rate' => $approvalRate,
                    ],
                    'revenue' => [
                        'total' => $totalRevenue,
                        'this_month' => $thisMonthRevenue,
                    ],
                    'commissions' => [
                        'total' => $totalCommission,
                        'this_month' => $thisMonthCommission,
                        'rate' => 3.0,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dashboard admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Merchants en attente de validation
     */
    public function pendingMerchants()
    {
        try {
            $merchants = Merchant::with('user:id,name,email')
                ->where(function($query) {
                    $query->where('is_verified', false)
                          ->orWhereNull('is_verified');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $merchants
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur merchants en attente', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Approuver un merchant
     */
    public function approveMerchant($id)
    {
        try {
            $merchant = Merchant::findOrFail($id);
            $merchant->is_verified = true;
            $merchant->save();

            Log::info('✅ Merchant approuvé', [
                'merchant_id' => $id,
                'merchant_name' => $merchant->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant approuvé avec succès',
                'data' => $merchant
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur approbation merchant', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation'
            ], 500);
        }
    }

    /**
     * Rejeter un merchant
     */
    public function rejectMerchant(Request $request, $id)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $merchant = Merchant::findOrFail($id);
            $merchant->is_verified = false;
            $merchant->rejection_reason = $request->reason;
            $merchant->save();

            Log::info('❌ Merchant rejeté', [
                'merchant_id' => $id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant rejeté',
                'data' => $merchant
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur rejet merchant', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet'
            ], 500);
        }
    }

    /**
     * Liste tous les merchants
     */
    public function merchants(Request $request)
    {
        try {
            $query = Merchant::with('user:id,name,email');

            // Filtrer par statut
            if ($request->has('status')) {
                if ($request->status === 'verified') {
                    $query->where('is_verified', true);
                } elseif ($request->status === 'pending') {
                    $query->where('is_verified', false);
                }
            }

            // Recherche
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('shop_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $merchants = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            // Ajouter des stats pour chaque merchant
            foreach ($merchants as $merchant) {
                $merchant->total_products = Product::where('merchant_id', $merchant->id)->count();
                $merchant->total_orders = Order::where('merchant_id', $merchant->id)->count();
                $merchant->total_revenue = Order::where('merchant_id', $merchant->id)
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->sum('total_price');
            }

            return response()->json([
                'success' => true,
                'data' => $merchants
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur liste merchants', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Liste tous les utilisateurs
     */
    public function users(Request $request)
    {
        try {
            $query = User::select('id', 'name', 'email', 'role', 'created_at');

            // Recherche
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filtrer par rôle
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            $users = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            // Ajouter des stats
            foreach ($users as $user) {
                $user->total_orders = Order::where('user_id', $user->id)->count();
                $user->total_spent = Order::where('user_id', $user->id)
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->sum('total_price');
            }

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur liste users', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Commandes récentes
     */
    
    public function orders(Request $request)
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

            // Filtrer par date
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filtrer par merchant
            if ($request->has('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            // Recherche
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            // Calculer les commissions et messages non lus
            $commissionRate = 0.03;
            foreach ($orders as $order) {
                $order->commission = $order->total_price * $commissionRate;
                
                // Vérifier s'il y a un litige
                $conversation = Conversation::where('order_id', $order->id)->first();
                $order->has_dispute = false;
                $order->unread_messages = 0;
                
                if ($conversation) {
                    $order->unread_messages = $conversation->messages()
                        ->where('is_read', false)
                        ->count();
                }
            }

            // Statistiques
            $stats = [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'confirmed' => Order::where('status', 'confirmed')->count(),
                'shipped' => Order::where('status', 'shipped')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
                'disputes' => Order::where('status', 'cancelled')->count(), // Approximation
            ];

            return response()->json([
                'success' => true,
                'data' => $orders,
                'stats' => $stats
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
    public function orderDetails($id)
    {
        try {
            $order = Order::with([
                'items.product.images',
                'merchant:id,name,shop_name,email,phone,user_id',
                'user:id,name,email'
            ])->findOrFail($id);

            // Conversation
            $conversation = Conversation::with(['messages.sender'])
                ->where('order_id', $order->id)
                ->first();

            // Commission
            $commissionRate = 0.03;
            $order->commission = $order->total_price * $commissionRate;

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

    /**
     * Commandes récentes (pour le dashboard)
     */
    public function recentOrders()
    {
        try {
            $orders = Order::with([
                'merchant:id,shop_name',
                'user:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

            // Ajouter la commission
            foreach ($orders as $order) {
                $order->commission = $order->total_price * 0.03;
            }

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur commandes récentes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Statistiques par région/pays
     */
    public function regionStats()
    {
        try {
            $stats = Order::select(
                    'shipping_city',
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total_price) as total_revenue'),
                    DB::raw('COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_orders'),
                    DB::raw('COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_orders')
                )
                ->groupBy('shipping_city')
                ->orderBy('total_orders', 'desc')
                ->limit(10)
                ->get();

            // Calculer le taux de satisfaction
            foreach ($stats as $stat) {
                $stat->satisfaction_rate = $stat->total_orders > 0 
                    ? round(($stat->delivered_orders / $stat->total_orders) * 100, 1)
                    : 0;
                    
                $stat->commission = $stat->total_revenue * 0.03;
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur stats régions admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }


    /**
     * Détails d'un merchant
     */
    public function merchantDetails($id)
    {
        try {
            $merchant = Merchant::with('user:id,name,email')
                ->findOrFail($id);

            // Stats du merchant
            $merchant->total_products = Product::where('merchant_id', $merchant->id)->count();
            $merchant->total_orders = Order::where('merchant_id', $merchant->id)->count();
            $merchant->total_revenue = Order::where('merchant_id', $merchant->id)
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->sum('total_price');
            $merchant->commission_generated = $merchant->total_revenue * 0.03;

            // Produits récents
            $merchant->recent_products = Product::where('merchant_id', $merchant->id)
                ->with('images')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Commandes récentes
            $merchant->recent_orders = Order::where('merchant_id', $merchant->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $merchant
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur détails merchant', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Merchant introuvable'
            ], 404);
        }
    }


    public function showOrder($id)
{
    $order = Order::with([
        'merchant',
        'items.product',
        'messages'
    ])->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => $order
    ]);
}


public function downloadOrder($id)
{
    $order = Order::with([
        'items.product',
        'merchant'
    ])->findOrFail($id);

    $pdf = Pdf::loadView('pdf.order', compact('order'));

    return $pdf->download('commande-'.$order->order_number.'.pdf');
}


}
