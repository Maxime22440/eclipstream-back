<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Middleware SuperAdminMiddleware
 *
 * Ce middleware vérifie que l'utilisateur est authentifié, qu'il a le rôle d'administrateur,
 * et que le mot de passe admin envoyé dans la requête correspond au mot de passe stocké dans la configuration.
 */
class SuperAdminMiddleware
{
    /**
     * Gère la requête entrante.
     *
     * @param Request $request La requête entrante.
     * @param Closure $next La prochaine action/middleware dans la chaîne.
     * @return mixed La réponse, ou le passage à la prochaine étape si toutes les vérifications sont réussies.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        // Vérifie que l'utilisateur est authentifié et est un administrateur.
        if (!$user || !$user->is_admin) {
            return response()->json(['error' => 'Unauthorized. Admin access only.'], 403);
        }

        // Récupère le mot de passe admin envoyé dans la requête.
        $adminPassword = $request->input('admin_password');
        $storedPassword = config('admin.password');

        // Vérifie que le mot de passe envoyé correspond au mot de passe stocké.
        if (!Hash::check($adminPassword, $storedPassword)) {
            return response()->json(['error' => 'Invalid admin password.'], 403);
        }

        return $next($request);
    }
}
