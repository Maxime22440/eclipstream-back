<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Content\ContentController;
use App\Http\Controllers\Api\Content\ContentRequestController;
use App\Http\Controllers\Api\Episode\EpisodeController;
use App\Http\Controllers\Api\Genre\GenreController;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/content/request', [ContentRequestController::class, 'store']);

Route::prefix('auth')->group(function (){
    Route::post('login', LoginController::class)
        ->middleware(['guest', 'web']);
    Route::post('/register', RegisterController::class)
        ->middleware(['guest', 'web']);
    Route::post('logout', LogoutController::class)->middleware(['auth:sanctum', 'web']);
});

// Routes protégées par auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Gestion des Genres
    Route::get('/genres', [GenreController::class, 'index']);

    // Gestion des Contenus
    Route::prefix('contents')->group(function () {
        Route::middleware(SuperAdminMiddleware::class)->post('/', [ContentController::class, 'addContent']);
        Route::get('/all-series', [ContentController::class, 'getAllSeries']);
        Route::get('/{seriesId}/seasons', [ContentController::class, 'getSeasons']);
        Route::middleware(SuperAdminMiddleware::class)->post('/{seriesId}/seasons', [ContentController::class, 'addSeasonToSeries']);
    });

    // Gestion des Épisodes
    Route::prefix('seasons/{seasonId}/episodes')->group(function () {
        Route::get('/', [EpisodeController::class, 'getEpisodes']);
        Route::middleware(SuperAdminMiddleware::class)->post('/', [EpisodeController::class, 'addEpisode']);
    });

});

Route::get('/contents/{uuid}', [ContentController::class, 'getContentByUuid']);

Route::get('/stream/episodes/{uuid}', function ($uuid) {
    Log::info('Requête reçue pour le streaming de l\'épisode.', ['uuid' => $uuid]);

    $episode = Episode::where('uuid', $uuid)->firstOrFail();
    $filePath = Storage::disk('private')->path($episode->video_link);

    if (!file_exists($filePath)) {
        Log::error('Fichier vidéo introuvable.', ['path' => $filePath]);
        abort(404, 'Fichier non trouvé.');
    }

    $fileSize = filesize($filePath);
    $start = 0;
    $end = $fileSize - 1;

    // Gestion des requêtes Range
    if (isset($_SERVER['HTTP_RANGE'])) {
        Log::info('Requête avec Range.', ['HTTP_RANGE' => $_SERVER['HTTP_RANGE']]);
        [$unit, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
        [$start, $end] = explode('-', $range);

        $start = intval($start);
        $end = ($end === '') ? $fileSize - 1 : intval($end);

        if ($start > $end || $end >= $fileSize) {
            abort(416, 'Invalid range');
        }
    }

    $length = $end - $start + 1;
    Log::info('Streaming partiel.', ['start' => $start, 'end' => $end, 'length' => $length]);

    // Ouvre le fichier pour le streaming
    $stream = fopen($filePath, 'rb');
    fseek($stream, $start);

    return response()->stream(function () use ($stream, $length) {
        $chunkSize = 8192; // Taille des chunks : 8 Ko
        while (!feof($stream) && $length > 0) {
            $data = fread($stream, min($chunkSize, $length));
            $length -= strlen($data);
            echo $data;
            flush(); // Envoie immédiatement les données au client
        }
        fclose($stream);
    }, 206, [ // Retourne un statut 206 Partial Content
        'Content-Type' => 'video/mp4',
        'Accept-Ranges' => 'bytes',
        'Content-Length' => $length,
        'Content-Range' => "bytes $start-$end/$fileSize",
    ]);
})->name('episode.stream')->middleware('auth:sanctum');
