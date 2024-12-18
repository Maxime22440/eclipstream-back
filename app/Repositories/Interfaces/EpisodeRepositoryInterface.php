<?php

namespace App\Repositories\Interfaces;

use App\Models\Content;
use App\Models\Episode;
use App\Models\Season;
use Illuminate\Support\Collection;

interface EpisodeRepositoryInterface
{
    public function getEpisodesBySeasonId(string $seasonId): Collection;
    public function addEpisodeToSeason(string $seasonId, array $episodeData): Episode;

}
