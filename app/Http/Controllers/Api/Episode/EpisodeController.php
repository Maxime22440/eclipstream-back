<?php

namespace App\Http\Controllers\Api\Episode;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEpisodeRequest;
use App\Repositories\Interfaces\EpisodeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use function App\Http\Controllers\str_contains;

class EpisodeController extends Controller
{
    protected EpisodeRepositoryInterface $episodeRepository;

    public function __construct(EpisodeRepositoryInterface $episodeRepository)
    {
        $this->episodeRepository = $episodeRepository;
    }

    /**
     * Récupérer tous les épisodes d'une saison spécifique
     */
    public function getEpisodes(string $seasonId): JsonResponse
    {
        try {
            $episodes = $this->episodeRepository->getEpisodesBySeasonId($seasonId);

            return response()->json(['episodes' => $episodes], 200);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des épisodes.', [
                'error' => $e->getMessage(),
                'season_id' => $seasonId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des épisodes', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Ajouter un nouvel épisode à une saison
     */
    public function addEpisode(StoreEpisodeRequest $request, string $seasonId): JsonResponse
    {
        try {
            $episodeData = $request->only(['episode_number', 'title', 'description', 'release_date', 'imdb_rating', 'duration']);
            $episode = $this->episodeRepository->addEpisodeToSeason($seasonId, $episodeData);

            return response()->json(['message' => 'Épisode ajouté avec succès', 'episode' => $episode], 201);
        } catch (Exception $e) {
            // Vérifie si l'exception est due à un doublon d'épisode
            if (str_contains($e->getMessage(), 'existe déjà')) {
                return response()->json(['error' => $e->getMessage()], 409); // Code HTTP 409 : Conflit
            }

            Log::error('Erreur lors de l\'ajout de l\'épisode.', [
                'error' => $e->getMessage(),
                'season_id' => $seasonId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de l\'ajout de l\'épisode'], 500);
        }
    }

}
