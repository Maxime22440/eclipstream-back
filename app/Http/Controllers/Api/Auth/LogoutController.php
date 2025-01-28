<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Déconnexion de l'utilisateur
        Auth::guard('web')->logout();

        // Invalidation de la session
        $request->session()->invalidate();

        // Régénération du token CSRF
        $request->session()->regenerateToken();

        // Supprimer le cookie de session
        $response = response()->json(['message' => __('Logged out successfully.')]);
        $response->withCookie(cookie()->forget(config('session.cookie')));

        return $response;
    }
}
