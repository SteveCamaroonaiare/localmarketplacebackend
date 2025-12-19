<?php
// app/Http/Middleware/EnsureIsAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Debug logging
        Log::info('Admin Middleware Check:', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'admin_role' => $user?->admin_role,
            'is_active_admin' => $user?->is_active_admin,
            'isAdmin' => $user?->isAdmin()
        ]);

        if (!$user) {
            Log::warning('Admin middleware: Utilisateur non authentifié');
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        if (!$user->isAdmin()) {
            Log::warning('Admin middleware: Accès refusé - pas admin', [
                'user_id' => $user->id,
                'admin_role' => $user->admin_role,
                'is_active_admin' => $user->is_active_admin
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux administrateurs.',
                'debug' => [
                    'user_id' => $user->id,
                    'admin_role' => $user->admin_role,
                    'is_active_admin' => $user->is_active_admin
                ]
            ], 403);
        }

        Log::info('Admin middleware: Accès autorisé', [
            'user_id' => $user->id,
            'admin_role' => $user->admin_role
        ]);

        return $next($request);
    }
}