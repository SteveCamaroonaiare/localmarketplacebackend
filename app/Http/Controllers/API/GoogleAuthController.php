<?php
// app/Http/Controllers/API/GoogleAuthController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Rediriger vers Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Gérer le callback de Google
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            Log::info('🔵 Google user:', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName()
            ]);

            // Vérifier si l'utilisateur existe déjà
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => bcrypt(uniqid()), // Mot de passe aléatoire
                    'role' => 'client',
                    'email_verified_at' => now(), // Google emails sont vérifiés
                ]);
                
                Log::info('✅ Nouvel utilisateur créé via Google', ['user_id' => $user->id]);
            } else {
                // Mettre à jour le google_id si nécessaire
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
                Log::info('✅ Utilisateur existant connecté via Google', ['user_id' => $user->id]);
            }

            // Générer un token API
            $token = $user->createToken('google-token')->plainTextToken;

            // Rediriger vers le frontend avec le token
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect("{$frontendUrl}/auth/google-callback?token={$token}&user=" . urlencode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ])));

        } catch (\Exception $e) {
            Log::error('❌ Erreur Google auth:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect(config('app.frontend_url') . '/auth/login?error=google_auth_failed');
        }
    }
}