<?php

namespace App\Repositories;

use App\Models\Actor;
use App\Models\Content;
use App\Models\Saga;
use App\Models\Season;
use App\Repositories\Interfaces\ContentRepositoryInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentRepository implements ContentRepositoryInterface
{
    public function addContent(array $data, string $type): Content
    {
        $posterPath = null;
        $thumbnailPath = null;
        $saga = null;

        // Gestion de la saga
        if (!empty($data['saga'])) {
            $saga = Saga::firstOrCreate(
                ['name' => $data['saga']],
                ['name' => $data['saga']]
            );
        }

        // Enregistrement du poster avec un nom UUID
        if (!empty($data['poster'])) {
            $poster = $data['poster'];
            $posterName = Str::uuid() . '.' . $poster->getClientOriginalExtension();
            $posterPath = $poster->storeAs('posters', $posterName, 'public');
        }

        // Enregistrement de la miniature avec un nom UUID
        if (!empty($data['thumbnail'])) {
            $thumbnail = $data['thumbnail'];
            $thumbnailName = Str::uuid() . '.' . $thumbnail->getClientOriginalExtension();
            $thumbnailPath = $thumbnail->storeAs('thumbnails', $thumbnailName, 'public');
        }

        // Création du film dans la base de données
        $content = Content::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'release_date' => $data['release_date'],
            'imdb_rating' => $data['imdb_rating'],
            'duration' => $data['duration'],
            'country' => $data['country'],
            'actors' => $data['actors'] ?? null,
            'poster_path' => $posterPath,
            'thumbnail_path' => $thumbnailPath,
            'rewarded' => $data['rewarded'],
            'saga_id' => $saga ? $saga->id : null,
            'type' => $type,
        ]);

        // Ajouter les genres
        if (!empty($data['genres'])) {
            $content->genres()->attach($data['genres']);
        }

        // Ajouter les acteurs
        if (!empty($data['actors'])) {
            // Décoder les acteurs si c'est une chaîne JSON
            $actors = is_string($data['actors']) ? json_decode($data['actors'], true) : $data['actors'];

            if (is_array($actors)) {
                // Créer les acteurs et récupérer leurs IDs
                $actorIds = collect($actors)->map(function ($actorName) {
                    return Actor::firstOrCreate(['name' => $actorName])->id;
                });

                // Associer les acteurs avec le contenu
                $content->actors()->syncWithoutDetaching($actorIds->toArray());
            } else {
                Log::error('Le champ actors n\'est ni un tableau ni un format valide.', ['actors' => $data['actors']]);
            }
        }

        Log::info(ucfirst($type) . ' ajouté avec succès dans la base de données.', ['content_id' => $content->id]);

        return $content;
    }

    // Récupère toutes les séries (classiques et animées)
    public function getAllSeries(): Collection
    {
        return Content::whereIn('type', ['series', 'anime-series'])->get();
    }

    // Récupère uniquement les séries classiques
    public function getClassicSeries(): Collection
    {
        return Content::where('type', 'series')->get();
    }

    // Récupère uniquement les séries animées
    public function getAnimeSeries(): Collection
    {
        return Content::where('type', 'anime-series')->get();
    }

    /**
     * @throws Exception
     */
    public function addSeasonToSeries(string $seriesId, array $seasonData): Season
    {
        try {
            // Vérifie que la série existe
            $series = Content::where('uuid', $seriesId)->where('type', 'series')->firstOrFail();

            // Crée une nouvelle saison associée à cette série
            $season = new Season();
            $season->content_id = $series->uuid;
            $season->season_number = $seasonData['season_number'];
            $season->total_episodes = $seasonData['total_episodes'];
            $season->save();

            Log::info('Saison ajoutée avec succès à la série.', [
                'series_id' => $seriesId,
                'season_id' => $season->id,
                'season_number' => $season->season_number,
                'total_episodes' => $season->total_episodes,
            ]);

            return $season;
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'ajout de la saison à la série.', [
                'error' => $e->getMessage(),
                'series_id' => $seriesId,
            ]);
            throw $e;
        }
    }

    public function getSeasonsBySeriesId(string $seriesId)
    {
        return Season::where('content_id', $seriesId)->get();
    }

    public function getContentByUuid(string $uuid): ?Content
    {
        // Charger uniquement le contenu de base avec ses relations communes
        $content = Content::where('uuid', $uuid)
            ->with(['actors', 'genres', 'categories'])
            ->first();

        // Si le contenu n'existe pas, on retourne null
        if (!$content) {
            return null;
        }

        // Si le type du contenu est une série, on charge les saisons et épisodes
        if (in_array($content->type, ['series', 'anime-series'])) {
            $content->load(['seasons.episodes']);
        }
        Log::info('Données chargées :', $content->toArray());
        return $content;
    }

}
