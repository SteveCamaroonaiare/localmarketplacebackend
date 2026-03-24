<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; 

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    
    public function register(Request $request)
    {
        DB::beginTransaction();
        
        try {
            Log::info('=== DÉBUT INSCRIPTION ===');
            Log::info('Données reçues:', $request->all());

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|max:20|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'register_as' => 'required|in:customer,merchant,client',
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Erreur validation:', $validator->errors()->toArray());
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('✅ Validation passée');
   // ✅ Déterminer le rôle
            $isMerchant = in_array($request->register_as, ['merchant', 'Merchant', 'MERCHANT']);
            $isCustomer = !$isMerchant; // ✅ Si c'est client, il est client

            // Vérifier si la table users a les bons champs
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                  'is_customer' => $isCustomer,
                'is_merchant' => $isMerchant,  
              'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&color=FFFFFF&background=FFEAA7',
                'wallet_balance' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Log::info('Données utilisateur préparées:', $userData);

            $user = User::create($userData);
            Log::info('✅ Utilisateur créé - ID: ' . $user->id);

             // Si marchand, créer le profil marchand
            if ($isMerchant) {
                Merchant::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'status' => 'pending',
                ]);
                
                // Définir le rôle actif comme marchand
                session(['active_role' => 'merchant']);
                                Log::info('✅ Profil marchand créé');

            }

            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('✅ Token créé');

            DB::commit();

            Log::info('=== INSCRIPTION RÉUSSIE ===');

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => [
        'token' => $token,

                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                       'is_customer' => (bool)$user->is_customer,
                        'is_merchant' => (bool)$user->is_merchant,
                        'has_shop' => $user->merchant ? !is_null($user->merchant->shop_name) : false,
                        'avatar' => $user->avatar,
                        'wallet_balance' => (float)$user->wallet_balance,
                    ],
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ ERREUR CRITIQUE inscription: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier les credentials
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

                   if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                         'is_customer' => (bool)$user->is_customer,
                'is_merchant' => (bool)$user->is_merchant,
                'has_shop' => $user->hasShop(),
                        'avatar' => $user->avatar,
                        'wallet_balance' => $user->wallet_balance,
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur connexion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Changer de rôle actif
     */
    public function switchRole(Request $request)
    {
        try {
            $user = $request->user();
            $newRole = $request->role; // 'customer' ou 'merchant'

            if ($newRole === 'merchant' && !$user->is_merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas marchand'
                ], 403);
            }

            session(['active_role' => $newRole]);

            return response()->json([
                'success' => true,
                'message' => 'Rôle changé avec succès',
                'data' => [
                    'active_role' => $newRole,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_customer' => $user->is_customer,
                        'is_merchant' => $user->is_merchant,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de rôle'
            ], 500);
        }
    }


    // 🔹 Profil
    public function profile()
    {
        return response()->json(Auth::user());
    }
    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur déconnexion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Récupérer l'utilisateur connecté
     */
    public function user(Request $request)
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
                    'phone' => $user->phone,

                     'is_customer' => (bool)$user->is_customer,
                        'is_merchant' => (bool)$user->is_merchant,
                        'has_shop' => $user->hasShop(),
                        'avatar' => $user->avatar,
                        'wallet_balance' => (float)$user->wallet_balance,
                ]
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Erreur récupération utilisateur: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur interne du serveur'
        ], 500);
    }
}

    /**
     * Générer un avatar basé sur le nom
     */
    private function generateAvatar($name)
    {
        $initials = strtoupper(substr($name, 0, 2));
        $colors = ['FF6B6B', '4ECDC4', '45B7D1', '96CEB4', 'FFEAA7', 'DDA0DD', '98D8C8'];
        $color = $colors[array_rand($colors)];
        
        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&color=FFFFFF&background=" . $color;

    }
}