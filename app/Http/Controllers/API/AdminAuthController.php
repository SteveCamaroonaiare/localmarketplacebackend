<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    /**
     * Connexion administrateur
     */
    public function login(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Chercher l'utilisateur
            $user = User::where('email', $request->email)->first();

            // Vérifier si l'utilisateur existe et est un admin actif
            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Tentative de connexion admin échouée', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            // ✅ Vérifier que c'est bien un administrateur
            if (!$user->isAdmin() && !$user->isSuperAdmin()) {
                Log::warning('Tentative de connexion admin par un non-admin', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'admin_role' => $user->admin_role
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Cette zone est réservée aux administrateurs.'
                ], 403);
            }

            // ✅ Vérifier que le compte admin est actif
            if (!$user->is_active_admin) {
                Log::warning('Tentative de connexion sur compte admin désactivé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Compte administrateur désactivé. Contactez le super-admin.'
                ], 403);
            }

            // Créer le token avec des capacités admin
            $token = $user->createToken('admin-token', ['admin-access'])->plainTextToken;

            // Log de succès
            Log::info('Connexion admin réussie', [
                'user_id' => $user->id,
                'email' => $user->email,
                'admin_role' => $user->admin_role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'admin_role' => $user->admin_role,
                        'is_active_admin' => $user->is_active_admin,
                        'avatar' => $user->avatar,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur connexion admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion'
            ], 500);
        }
    }

    /**
     * Déconnexion admin
     */
    public function logout(Request $request)
    {
        try {
            // Révoquer le token actuel
            $request->user()->currentAccessToken()->delete();

            Log::info('Déconnexion admin réussie', [
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur déconnexion admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }

    /**
     * Récupérer les infos de l'admin connecté
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'admin_role' => $user->admin_role,
                        'is_active_admin' => $user->is_active_admin,
                        'avatar' => $user->avatar,
                        'created_at' => $user->created_at,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations'
            ], 500);
        }
    }
}