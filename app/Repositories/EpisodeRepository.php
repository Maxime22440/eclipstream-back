<?php

namespace App\Repositories;

use App\Models\Content;
use App\Models\Episode;
use App\Models\Saga;
use App\Models\Season;
use App\Repositories\Interfaces\ContentRepositoryInterface;
use App\Repositories\Interfaces\EpisodeRepositoryInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EpisodeRepository implements EpisodeRepositoryInterface
{
    public function getEpisodesBySeasonId(string $seasonId): Collection
    {
        return Episode::where('season_id', $seasonId)->get();
    }

    public function addEpisodeToSeason(string $seasonId, array $episodeData): Episode
    {
        // Vérifier si un épisode avec le même numéro existe déjà pour cette saison
        $existingEpisode = Episode::where('season_id', $seasonId)
            ->where('episode_number', $episodeData['episode_number'])
            ->first();

        if ($existingEpisode) {
            throw new Exception("L'épisode numéro {$episodeData['episode_number']} existe déjà pour cette saison.");
        }

        $episode = new Episode();
        $episode->season_id = $seasonId;
        $episode->episode_number = $episodeData['episode_number'];
        $episode->title = $episodeData['title'];
        $episode->description = $episodeData['description'];
        $episode->release_date = $episodeData['release_date'];
        $episode->imdb_rating = $episodeData['imdb_rating'];
        $episode->duration = $episodeData['duration'];
        $episode->save();

        return $episode;
    }
}
