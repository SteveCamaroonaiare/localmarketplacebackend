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
            // ⚠️ Important: shop_name doit être null par défaut
            'shop_name' => null,
        ]);

        // ✅ GÉNÉRER UN TOKEN COMME DANS LA MÉTHODE LOGIN
        $token = $merchant->createToken('merchant-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte vendeur créé avec succès 🎉',
            'data' => [
                'token' => $token,
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'email' => $merchant->email,
                    'phone' => $merchant->phone,
                    'role' => 'merchant',
                    'shop_name' => $merchant->shop_name, // null
                    'logo' => $merchant->logo,
                    'created_at' => $merchant->created_at,
                ]
            ]
        ], 201);
    }

    // 🔹 Connexion vendeur (déjà correct)
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

        $token = $merchant->createToken('merchant-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie ✅',
            'data' => [
                'token' => $token,
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'email' => $merchant->email,
                    'phone' => $merchant->phone,
                    'role' => 'merchant',
                    'shop_name' => $merchant->shop_name,
                    'logo' => $merchant->logo,
                ]
            ]
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

    // 🔹 Mise à jour du profil
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