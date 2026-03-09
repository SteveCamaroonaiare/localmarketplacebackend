<?php
// app/Http/Controllers/API/GoogleMerchantController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleMerchantController extends Controller
{
    /**
     * Rediriger vers Google pour les marchands
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Gérer le callback de Google pour les marchands
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            Log::info('🔵 Google Merchant:', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName()
            ]);

            DB::beginTransaction();

            // Vérifier si l'utilisateur existe déjà
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Créer un nouvel utilisateur avec rôle marchand
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => bcrypt(uniqid()),
                    'role' => 'merchant',
                    'email_verified_at' => now(),
                ]);
                
                Log::info('✅ Nouveau marchand créé via Google', ['user_id' => $user->id]);
                
                // Créer le profil marchand
                $merchant = Merchant::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'merchant',
                    'status' => 'pending',
                ]);
            } else {
                // Vérifier si l'utilisateur existant est bien marchand
                if ($user->role !== 'merchant') {
                    DB::rollBack();
                    
                    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
                    return redirect("{$frontendUrl}/merchant/login?error=" . urlencode("Cet email est déjà utilisé comme client. Veuillez utiliser un autre compte Google."));
                }
                
                // Récupérer ou créer le profil marchand
                $merchant = Merchant::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => 'merchant',
                        'status' => 'pending',
                    ]
                );
                
                // Mettre à jour le google_id si nécessaire
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
                
                Log::info('✅ Marchand existant connecté via Google', ['user_id' => $user->id]);
            }

            DB::commit();

            // Générer un token API
            $token = $user->createToken('google-merchant-token')->plainTextToken;

            // Préparer les données utilisateur
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => 'merchant',
                'merchant_id' => $merchant->id,
                'has_shop' => !is_null($merchant->shop_name),
                'has_subscription' => !is_null($merchant->subscription_id),
                'shop_name' => $merchant->shop_name,
            ];

            // Déterminer la redirection
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            
            // ✅ REDIRECTION INTELLIGENTE
            if (!$merchant->shop_name) {
                // Pas de boutique → créer la boutique
                $redirectPath = '/merchant/create-shop';
            } elseif (!$merchant->subscription_id) {
                // Boutique créée mais pas d'abonnement
                $redirectPath = '/merchant/subscription';
            } else {
                // Tout est OK → dashboard
                $redirectPath = '/merchant/dashboard';
            }

            // ✅ Encoder les données pour les passer dans l'URL
            $encodedUser = urlencode(json_encode($userData));
            
            return redirect("{$frontendUrl}{$redirectPath}?token={$token}&user={$encodedUser}");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Erreur Google Merchant:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/merchant/login?error=google_auth_failed");
        }
    }
}