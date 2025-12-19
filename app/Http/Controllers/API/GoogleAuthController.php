<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    
    /**
     * Rediriger vers Google pour l'authentification
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Callback après authentification Google
     */
    public function handleGoogleCallback()
    {
        try {
            // Récupérer les informations de l'utilisateur depuis Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Vérifier si l'utilisateur existe déjà avec cet email ou google_id
            $user = User::where('email', $googleUser->getEmail())
                ->orWhere('google_id', $googleUser->getId())
                ->first();

            if ($user) {
                // Mettre à jour le google_id si nécessaire
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            } else {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(), // Utiliser l'avatar Google
                    'phone' => '', // Peut être complété plus tard
                    'email_verified_at' => now(),
                ]);
            }

            // Créer un token d'authentification
            $token = $user->createToken('auth_token')->plainTextToken;

            // Construire l'URL de redirection vers le frontend avec les données
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = $frontendUrl . '/auth/callback?' . http_build_query([
                'success' => 'true',
                'token' => $token,
                'user' => json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'wallet_balance' => (float) $user->wallet_balance,
                ])
            ]);

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            \Log::error('Erreur authentification Google: ' . $e->getMessage());
            
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = $frontendUrl . '/auth/callback?' . http_build_query([
                'success' => 'false',
                'error' => 'Erreur lors de l\'authentification Google'
            ]);

            return redirect($redirectUrl);
        }
    }

    /**
     * Version API du callback (retourne JSON au lieu de rediriger)
     */
    public function handleGoogleCallbackApi()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())
                ->orWhere('google_id', $googleUser->getId())
                ->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'phone' => '',
                    'email_verified_at' => now(),
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Authentification Google réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'avatar' => $user->avatar,
                        'wallet_balance' => (float) $user->wallet_balance,
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur authentification Google API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'authentification Google'
            ], 500);
        }
    }
}