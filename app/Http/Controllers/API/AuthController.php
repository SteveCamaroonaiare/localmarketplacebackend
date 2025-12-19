<?php

namespace App\Http\Controllers\API;

use App\Models\User;
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
                'role' => 'required|in:client,merchant',
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

            // Vérifier si la table users a les bons champs
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&color=FFFFFF&background=FFEAA7',
                'wallet_balance' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Log::info('Données utilisateur préparées:', $userData);

            $user = User::create($userData);
            Log::info('✅ Utilisateur créé - ID: ' . $user->id);

            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('✅ Token créé');

            DB::commit();

            Log::info('=== INSCRIPTION RÉUSSIE ===');

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                        'wallet_balance' => $user->wallet_balance,
                    ],
                    'token' => $token
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

                    if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
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
                    'avatar' => $user->avatar,
                    'wallet_balance' => (float) $user->wallet_balance,
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