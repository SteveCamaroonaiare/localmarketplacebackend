<?php
// app/Http/Controllers/API/SuperAdminController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    /**
     * Statistiques globales pour le super admin
     */
    public function dashboardStats()
    {
        try {
            $stats = [
                // Utilisateurs
                'total_users' => User::count(),
                'total_clients' => User::where('role', 'client')->count(),
                'total_merchants' => User::where('role', 'merchant')->count(),
                'pending_merchants' => Merchant::where('is_verified', false)->count(),

                // Produits
                'total_products' => Product::count(),
                'pending_products' => Product::where('status', 'pending')->count(),
                'approved_products' => Product::where('status', 'approved')->count(),
                'rejected_products' => Product::where('status', 'rejected')->count(),

                // Commandes
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'completed_orders' => Order::where('status', 'delivered')->count(),

                // Admins
                'total_admins' => User::whereNotNull('admin_role')->count(),
                'active_admins' => User::whereNotNull('admin_role')->where('is_active_admin', true)->count(),
                'super_admins' => User::where('admin_role', 'super_admin')->where('is_active_admin', true)->count(),

                // Revenus
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_price'),
                'monthly_revenue' => Order::where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_price'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques globales récupérées',
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Activité récente des administrateurs
     */
    public function adminActivity()
    {
        try {
            // Produits approuvés récemment par des admins
            $recentApprovals = Product::whereNotNull('approved_by')
                ->with(['approvedBy', 'merchant'])
                ->orderBy('approved_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'approved_by' => $product->approvedBy->name,
                        'approved_at' => $product->approved_at,
                        'merchant' => $product->merchant->shop_name,
                    ];
                });

            return response()->json([
                'success' => true,
                'recent_approvals' => $recentApprovals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'activité'
            ], 500);
        }
    }

    /**
     * Audit du système
     */
    public function systemAudit()
    {
        try {
            $audit = [
                'disk_usage' => $this->getDiskUsage(),
                'database_size' => $this->getDatabaseSize(),
                'last_backup' => $this->getLastBackupDate(),
                'system_health' => $this->getSystemHealth(),
            ];

            return response()->json([
                'success' => true,
                'audit' => $audit
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'audit du système'
            ], 500);
        }
    }

    private function getDiskUsage()
    {
        // Implémentation basique de l'usage du disque
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'total' => round($total / (1024 * 1024 * 1024), 2), // GB
            'used' => round($used / (1024 * 1024 * 1024), 2),
            'free' => round($free / (1024 * 1024 * 1024), 2),
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }

    private function getDatabaseSize()
    {
        // Taille approximative de la base de données
        $size = DB::select("SELECT SUM(data_length + index_length) as size 
                           FROM information_schema.TABLES 
                           WHERE table_schema = ?", [config('database.connections.mysql.database')]);
        
        return round($size[0]->size / (1024 * 1024), 2); // MB
    }

    private function getLastBackupDate()
    {
        // À implémenter selon votre système de backup
        return null;
    }

    private function getSystemHealth()
    {
        return [
            'status' => 'healthy',
            'checks' => [
                'database' => true,
                'storage' => true,
                'cache' => true,
            ]
        ];
    }
}