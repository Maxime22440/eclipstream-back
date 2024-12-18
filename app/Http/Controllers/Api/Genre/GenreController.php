<?php

namespace App\Http\Controllers\Api\Genre;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\GenreRepositoryInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GenreController extends Controller
{
    protected GenreRepositoryInterface $genreRepository;

    public function __construct(GenreRepositoryInterface $genreRepository)
    {
        $this->genreRepository = $genreRepository;
    }

    public function index(): JsonResponse
    {
        try {
            $genres = $this->genreRepository->getAllGenres();

            if ($genres->isEmpty()) {
                Log::warning('Aucun genre trouvé');
                return response()->json(['message' => 'Aucun genre trouvé'], 404);
            }

            Log::info('Genres récupérés avec succès.', ['count' => $genres->count()]);

            return response()->json($genres);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des genres', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Erreur lors de la récupération des genres'], 500);
        }
    }
}

