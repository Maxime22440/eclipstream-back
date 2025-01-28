<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Content\ContentController;
use App\Http\Controllers\Api\Content\ContentRequestController;
use App\Http\Controllers\Api\Episode\EpisodeController;
use App\Http\Controllers\Api\Genre\GenreController;
use App\Http\Controllers\Api\Streaming\StreamingController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/stream/{uuid}', [StreamingController::class, 'streamMovie'])
    ->name('movie.stream')
    ->middleware('auth:sanctum');

Route::get('/stream/episodes/{uuid}', [StreamingController::class, 'streamEpisode'])
    ->name('episode.stream')
    ->middleware('auth:sanctum');
