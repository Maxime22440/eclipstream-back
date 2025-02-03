<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Content\ContentController;
use App\Http\Controllers\Api\Content\ContentRequestController;
use App\Http\Controllers\Api\Episode\EpisodeController;
use App\Http\Controllers\Api\Genre\GenreController;
use App\Http\Controllers\Api\Streaming\HLSStreamingController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes d'authentification API
Route::prefix('auth')->group(function () {
    Route::post('login', [LoginController::class, 'login'])
        ->middleware(['guest:api'])->name('login');

    Route::post('register', [RegisterController::class, 'register'])
        ->middleware(['guest:api']);

    Route::post('logout', [LogoutController::class, 'logout'])
        ->middleware(['auth:sanctum']);
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

    // Générer une URL signée pour un film HLS
    Route::get('/signed-stream/hls/playlist/movies/{movieUuid}', [HLSStreamingController::class, 'streamSignedPlaylistForMovie'])
        ->name('signed.stream.hls.playlist.movie');

    Route::get('/signed-stream/hls/playlist/episodes/{episodeUuid}', [HLSStreamingController::class, 'streamSignedPlaylistForEpisode'])
        ->name('signed.stream.hls.playlist.episode');

    // Routes de streaming HLS protégées par le middleware 'signed'
    Route::get('/stream/hls/movies/{movieUuid}/{filename}', [HLSStreamingController::class, 'streamMovieHLS'])
        ->name('stream.hls.movie')
        ->middleware('signed');

    Route::get('/stream/hls/series/{seriesUuid}/{season}/{episodeUuid}/{filename}', [HLSStreamingController::class, 'streamEpisodeHLS'])
        ->name('stream.hls.episode')
        ->middleware('signed');

});
Route::post('/content/request', [ContentRequestController::class, 'store']);
Route::get('/contents/{uuid}', [ContentController::class, 'getContentByUuid']);
