<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        // Vérifie si l'utilisateur est un admin
        if (!$user || !$user->is_admin) {
            return response()->json(['error' => 'Unauthorized. Admin access only.'], 403);
        }

        // Récupère le mot de passe admin envoyé dans la requête
        $adminPassword = $request->input('admin_password');
        $storedPassword = config('admin.password');

        if (!Hash::check($adminPassword, $storedPassword)) {
            return response()->json(['error' => 'Invalid admin password.'], 403);
        }

        return $next($request);
    }

}
