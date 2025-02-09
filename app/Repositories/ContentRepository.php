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
    /**
     * Ajoute un contenu (film, série, etc.) dans la base de données.
     *
     * Cette méthode gère la création ou la récupération d'une saga, l'enregistrement des fichiers (poster, thumbnail),
     * et l'association des genres et acteurs au contenu.
     *
     * @param array  $data Données du contenu.
     * @param string $type Type de contenu (par exemple, "film", "series").
     *
     * @return Content L'instance du contenu créé.
     *
     * @throws Exception En cas d'erreur lors de l'enregistrement.
     */
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

        // Création du contenu dans la base de données
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

        // Association des genres
        if (!empty($data['genres'])) {
            $content->genres()->attach($data['genres']);
        }

        // Association des acteurs
        if (!empty($data['actors'])) {
            // Décodage si les acteurs sont fournis en JSON
            $actors = is_string($data['actors']) ? json_decode($data['actors'], true) : $data['actors'];

            if (is_array($actors)) {
                $actorIds = collect($actors)->map(function ($actorName) {
                    return Actor::firstOrCreate(['name' => $actorName])->id;
                });

                // Attachement sans détachement des autres relations déjà existantes
                $content->actors()->syncWithoutDetaching($actorIds->toArray());
            } else {
                Log::error('Le champ actors n\'est ni un tableau ni un format valide.', ['actors' => $data['actors']]);
            }
        }

        Log::info(ucfirst($type) . ' ajouté avec succès dans la base de données.', ['content_id' => $content->id]);

        return $content;
    }

    /**
     * Récupère toutes les séries (classiques et animées).
     *
     * @return Collection
     */
    public function getAllSeries(): Collection
    {
        return Content::whereIn('type', ['series', 'anime-series'])->get();
    }

    /**
     * Récupère uniquement les séries classiques.
     *
     * @return Collection
     */
    public function getClassicSeries(): Collection
    {
        return Content::where('type', 'series')->get();
    }

    /**
     * Récupère uniquement les séries animées.
     *
     * @return Collection
     */
    public function getAnimeSeries(): Collection
    {
        return Content::where('type', 'anime-series')->get();
    }

    /**
     * Ajoute une saison à une série.
     *
     * @param string $seriesId L'UUID de la série.
     * @param array  $seasonData Données de la saison (season_number, total_episodes).
     *
     * @return Season La saison créée.
     *
     * @throws Exception Si la série n'existe pas ou en cas d'erreur lors de la création.
     */
    public function addSeasonToSeries(string $seriesId, array $seasonData): Season
    {
        try {
            $series = Content::where('uuid', $seriesId)->where('type', 'series')->firstOrFail();

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

    /**
     * Récupère toutes les saisons d'une série.
     *
     * @param string $seriesId L'UUID de la série.
     *
     * @return Collection
     */
    public function getSeasonsBySeriesId(string $seriesId): Collection
    {
        return Season::where('content_id', $seriesId)->get();
    }

    /**
     * Récupère un contenu par son UUID, en chargeant ses relations communes.
     *
     * Si le contenu est une série, charge également ses saisons et épisodes.
     *
     * @param string $uuid L'UUID du contenu.
     *
     * @return Content|null
     */
    public function getContentByUuid(string $uuid): ?Content
    {
        $content = Content::where('uuid', $uuid)
            ->with(['actors', 'genres', 'categories'])
            ->first();

        if (!$content) {
            return null;
        }

        if (in_array($content->type, ['series', 'anime-series'])) {
            $content->load(['seasons.episodes']);
        }
        Log::info('Données chargées :', $content->toArray());
        return $content;
    }

    /**
     * Récupère les contenus non uploadés, à l'exception des séries.
     *
     * @return Collection
     */
    public function getNotUploadedContent(): Collection
    {
        return Content::where('is_uploaded', 0)
            ->where('type', '!=', 'series')
            ->get();
    }

    /**
     * Met à jour le champ is_uploaded à 1 pour un contenu donné.
     *
     * @param string $contentUuid L'UUID du contenu.
     *
     * @return bool
     */
    public function markContentAsUploaded(string $contentUuid): bool
    {
        $content = Content::where('uuid', $contentUuid)->first();
        if (!$content) {
            return false;
        }
        $content->is_uploaded = 1;
        return $content->save();
    }

    /**
     * Récupère un film au hasard (content type 'movie').
     *
     * @return Content|null
     */
    public function getRandomMovie(): ?Content
    {
        return Content::where('type', 'movie')
            ->inRandomOrder()
            ->first();
    }

    /**
     * Incrémente le compteur de vues d'un contenu.
     *
     * Cette méthode permet d'augmenter le champ total_views pour un contenu donné,
     * qu'il s'agisse d'un film, d'une série ou d'un animé.
     *
     * @param string $uuid L'UUID du contenu à incrémenter.
     *
     * @return void
     */
    public function incrementViews(string $uuid): void
    {
        Content::where('uuid', $uuid)->increment('total_views');
    }

    public function getAllMoviesOrderedByCreation()
    {
        return Content::where('type', 'movie')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLatestMovies(int $limit)
    {
        return Content::where('type', 'movie')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTopMovies(int $limit)
    {
        return Content::where('type', 'movie')
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();
    }
}
