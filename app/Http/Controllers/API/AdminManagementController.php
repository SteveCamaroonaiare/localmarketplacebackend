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
}