<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Invalider toute session existante
        $request->session()->invalidate();

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            // Régénérer la session
            $request->session()->regenerate();

            // Supprimer tous les cookies sauf XSRF-TOKEN et eclipstream_back_session
            $response = response()->json(['message' => __('Welcome!')]);
            $this->removeUnwantedCookies($request, $response);

            return $response;
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    /**
     * Supprime les cookies indésirables sauf XSRF-TOKEN et le cookie de session.
     *
     * @param Request $request
     * @param JsonResponse $response
     * @return void
     */
    protected function removeUnwantedCookies(Request $request, JsonResponse $response): void
    {
        $sessionCookieName = config('session.cookie', Str::slug(env('APP_NAME', 'laravel'), '_') . '_session');
        $allowedCookies = ['XSRF-TOKEN', $sessionCookieName];
        $currentCookies = $request->cookies->keys();

        foreach ($currentCookies as $cookieName) {
            if (!in_array($cookieName, $allowedCookies)) {
                $response->headers->setCookie(Cookie::forget($cookieName));
            }
        }
    }
}
