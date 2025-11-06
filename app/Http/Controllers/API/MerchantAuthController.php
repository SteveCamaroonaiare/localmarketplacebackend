<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class MerchantAuthController extends Controller
{
    // 🔹 Inscription vendeur
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:merchants',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => 'in:merchant'
            
        ]);

        $merchant = Merchant::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'merchant',
        ]);

        return response()->json([
            'message' => 'Compte vendeur créé avec succès 🎉',
            'merchant' => $merchant,
                'role' => 'merchant'

        ], 201);
    }

    // 🔹 Connexion vendeur
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $merchant = Merchant::where('email', $request->email)->first();

        if (!$merchant || !Hash::check($request->password, $merchant->password)) {
            return response()->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        // Optionnel : Générer un token JWT ou Sanctum si besoin
        $token = $merchant->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie ✅',
            'merchant' => $merchant,
            'token' => $token
        ]);
    }

    // 🔹 Détails du compte
    public function profile()
    {
        return response()->json(Auth::user());
    }

    // 🔹 Déconnexion
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie 👋']);
    }

 public function updateProfile(Request $request)
{
    try {
        $merchant = $request->user();

        if (!$merchant) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $merchant->update($request->only([
            'shop_name',
            'shop_address',
            'country',
            'category',
            'payment_method',
            'payment_account',
        ]));

        return response()->json([
            'message' => 'Profil mis à jour avec succès ✅',
            'merchant' => $merchant
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
    
}


}
