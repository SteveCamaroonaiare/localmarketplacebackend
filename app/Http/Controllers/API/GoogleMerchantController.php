<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Merchant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class GoogleMerchantController extends Controller
{
    // Redirection vers Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Callback Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Vérifier si le vendeur existe déjà
            $merchant = Merchant::where('email', $googleUser->getEmail())->first();

            if (!$merchant) {
                $merchant = Merchant::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)), // mot de passe aléatoire
                ]);
            }

            // Crée un token API (ou une session selon ton système)
            $token = $merchant->createToken('auth_token')->plainTextToken;

            return redirect("http://localhost:3000/merchant/dashboard?token={$token}");
        } catch (\Exception $e) {
            return redirect("http://localhost:3000/merchant/login?error=" . urlencode($e->getMessage()));
        }
    }
}
