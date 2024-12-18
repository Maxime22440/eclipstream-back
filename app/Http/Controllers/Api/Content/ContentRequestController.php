<?php

namespace App\Http\Controllers\Api\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

class ContentRequestController extends Controller
{
    /**
     * Enregistrer une nouvelle demande de contenu sans connexion
     */
    public function store(ContentRequest $request): JsonResponse
    {
        $ip = $request->ip(); // Récupère l'adresse IP de l'utilisateur

        // Limiter les requêtes à 5 tentatives par minute par IP pour éviter les abus
        if (RateLimiter::tooManyAttempts('request-content:' . $ip, 5)) {
            return response()->json(['message' => 'Trop de demandes. Veuillez réessayer plus tard.'], 429);
        }

        DB::beginTransaction();

        try {
            Log::info('Demande de contenu soumise', [
                'ip' => $ip,
                'content_title' => $request->content_title,
                'content_type' => $request->content_type ?? 'non spécifié',
            ]);

            $userId = auth()->check() ? auth()->id() : null;

            $contentRequest = Request::create([
                'content_title' => $request->content_title,
                'content_type' => $request->content_type,
                'user_id' => $userId,
            ]);

            DB::commit();

            // Incrémenter le compteur de tentatives pour l'IP
            RateLimiter::hit('request-content:' . $ip, 60);

            Log::info('Requête de contenu ajoutée avec succès', [
                'request_id' => $contentRequest->id,
                'ip' => $ip,
                'content_title' => $contentRequest->content_title,
                'content_type' => $contentRequest->content_type,
            ]);

            return response()->json(['message' => 'Votre demande a été enregistrée avec succès.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la requête d\'ajout de contenu', [
                'ip' => $ip,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Erreur lors de l\'enregistrement du contenu.'], 500);
        }
    }
}
