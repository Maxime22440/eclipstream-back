<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LoginThrottleMiddleware
{
    private const MAX_ATTEMPTS = 5; // Nombre maximal de tentatives autorisées
    private const DECAY_MINUTES = 1; // Durée en minutes avant de réinitialiser le compteur

    public function handle(Request $request, Closure $next)
    {
        $email = $request->input('email');

        // Si l'email est manquant, ignorer le middleware
        if (!$email) {
            return $next($request);
        }

        $key = "login_attempts:{$email}";
        $attempts = Cache::get($key, 0);

        // Vérifie si le nombre de tentatives a dépassé la limite
        if ($attempts >= self::MAX_ATTEMPTS) {
            return response()->json([
                'message' => 'Trop de tentatives. Veuillez réessayer dans ' . self::DECAY_MINUTES . ' minute(s).'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Appeler le contrôleur
        $response = $next($request);

        // Incrémenter le compteur pour les erreurs 401 et 404
        if (in_array($response->getStatusCode(), [Response::HTTP_UNAUTHORIZED, Response::HTTP_NOT_FOUND])) {
            Cache::put($key, $attempts + 1, now()->addMinutes(self::DECAY_MINUTES));
        } else {
            // Si l'authentification réussit (code 200), réinitialiser le compteur
            Cache::forget($key);
        }

        return $response;
    }
}
