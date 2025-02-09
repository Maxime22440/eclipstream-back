<?php

namespace App\Http\Controllers\Api\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\StoreSeasonRequest;
use App\Repositories\Interfaces\ContentRepositoryInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ContentController extends Controller
{
    protected ContentRepositoryInterface $contentRepository;

    public function __construct(ContentRepositoryInterface $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    public function addContent(StoreContentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $type = $validated['type'];
            $content = $this->contentRepository->addContent($validated, $type);

            return response()->json(['message' => ucfirst($type) . ' ajouté avec succès', 'content' => $content], 201);
        } catch (Exception $e) {
            Log::error('Une erreur est survenue lors de l\'ajout du contenu.', [
                'error' => $e->getMessage(),
                'title' => $validated['title'],
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de l\'ajout du contenu', 'details' => $e->getMessage()], 500);
        }
    }

    public function getAllSeries(): JsonResponse
    {
        Log::info('Séries trouvées :');
        try {
            $series = $this->contentRepository->getAllSeries();
            return response()->json(['series' => $series], 200);
        } catch (Exception $e) {
            Log::error('Une erreur est survenue lors de la récupération des séries.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des séries', 'details' => $e->getMessage()], 500);
        }
    }

    public function addSeasonToSeries(StoreSeasonRequest $request, string $seriesId): JsonResponse
    {

        try {
            $seasonData = $request->only(['season_number', 'total_episodes']);
            Log::info($seriesId);
            $season = $this->contentRepository->addSeasonToSeries($seriesId, $seasonData);

            return response()->json(['message' => 'Saison ajoutée avec succès', 'season' => $season], 201);
        } catch (Exception $e) {
            Log::error('Une erreur est survenue lors de l\'ajout de la saison.', [
                'error' => $e->getMessage(),
                'series_id' => $seriesId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de l\'ajout de la saison', 'details' => $e->getMessage()], 500);
        }
    }

    public function getSeasons(string $seriesId): JsonResponse
    {
        try {
            // Utilisation du repository pour récupérer les saisons de la série spécifiée
            $seasons = $this->contentRepository->getSeasonsBySeriesId($seriesId);

            return response()->json(['seasons' => $seasons], 200);
        } catch (Exception $e) {
            Log::error('Une erreur est survenue lors de la récupération des saisons.', [
                'error' => $e->getMessage(),
                'series_id' => $seriesId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des saisons', 'details' => $e->getMessage()], 500);
        }
    }

    public function getContentByUuid(string $uuid): JsonResponse
    {
        try {
            // Récupération du contenu via le repository
            $content = $this->contentRepository->getContentByUuid($uuid);

            if (!$content) {
                return response()->json(['error' => 'Contenu introuvable'], 404);
            }

            // Retourner le contenu directement (Laravel inclut automatiquement les relations chargées)
            return response()->json($content, 200);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération du contenu.', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de la récupération du contenu', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les contenus non uploadés (is_uploaded = 0)
     *
     * @return JsonResponse
     */
    public function getNotUploadedContent(): JsonResponse
    {
        try {
            $contents = $this->contentRepository->getNotUploadedContent();
            return response()->json(['contents' => $contents], 200);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des contenus non uploadés.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération des contenus non uploadés',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère un film au hasard.
     *
     * @return JsonResponse
     */
    public function getRandomMovie(): JsonResponse
    {
        try {
            $movie = $this->contentRepository->getRandomMovie();

            if (!$movie) {
                return response()->json(['error' => 'Aucun film trouvé'], 404);
            }

            return response()->json(['movie' => $movie], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération d\'un film aléatoire.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error'   => 'Une erreur est survenue lors de la récupération d\'un film aléatoire',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllMovies(): JsonResponse
    {
        try {
            $movies = $this->contentRepository->getAllMoviesOrderedByCreation();
            return response()->json(['movies' => $movies], 200);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération de tous les films.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération de tous les films',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function getLatestMovies(): JsonResponse
    {
        try {
            $movies = $this->contentRepository->getLatestMovies(10);
            return response()->json(['movies' => $movies], 200);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des derniers films.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération des derniers films',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les 10 films les plus regardés.
     *
     * @return JsonResponse
     */
    public function getTopViewedMovies(): JsonResponse
    {
        try {
            $movies = $this->contentRepository->getTopMovies(10);
            return response()->json(['movies' => $movies], 200);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des films les plus regardés.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération des films les plus regardés',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
