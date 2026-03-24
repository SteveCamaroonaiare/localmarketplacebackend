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
                // ✅ Créer un nouvel utilisateur avec Option 1
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => bcrypt(uniqid()),
                    'is_customer' => true,      // ✅ Client par défaut
                    'is_merchant' => false,     // ❌ Pas marchand
                    'wallet_balance' => 0,
                    'email_verified_at' => now(),
                ]);
                
                Log::info('✅ Nouvel utilisateur créé via Google', [
                    'user_id' => $user->id,
                    'is_customer' => $user->is_customer,
                    'is_merchant' => $user->is_merchant
                ]);
            } else {
                // ✅ Mettre à jour les champs Option 1 si nécessaire
                $updateData = [];
                
                if (!$user->google_id) {
                    $updateData['google_id'] = $googleUser->getId();
                }
                if (!$user->avatar && $googleUser->getAvatar()) {
                    $updateData['avatar'] = $googleUser->getAvatar();
                }
                if (empty($updateData)) {
                    $updateData['updated_at'] = now();
                }
                
                if (!empty($updateData)) {
                    $user->update($updateData);
                }
                
                Log::info('✅ Utilisateur existant connecté via Google', [
                    'user_id' => $user->id,
                    'is_customer' => $user->is_customer,
                    'is_merchant' => $user->is_merchant
                ]);
            }

            // Générer un token API
            $token = $user->createToken('google-token')->plainTextToken;

            // ✅ Structure pour Option 1
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'is_customer' => (bool)$user->is_customer,
                'is_merchant' => (bool)$user->is_merchant,
                'has_shop' => $user->hasShop(),
            ];

            // Rediriger vers le frontend avec le token
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/auth/google-callback?token={$token}&user=" . urlencode(json_encode($userData)));

        } catch (\Exception $e) {
            Log::error('❌ Erreur Google auth:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect(env('FRONTEND_URL') . '/auth/login?error=google_auth_failed');
        }
    }
}