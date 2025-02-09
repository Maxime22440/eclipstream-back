<?php

namespace App\Repositories\Interfaces;

use App\Models\Episode;
use Illuminate\Support\Collection;

interface EpisodeRepositoryInterface
{
    public function getEpisodesBySeasonId(string $seasonId): Collection;
    public function addEpisodeToSeason(string $seasonId, array $episodeData): Episode;
    public function getNotUploadedEpisodes(): Collection;
    public function getEpisodeByUuid(string $episodeUuid): ?Episode;
    public function markEpisodeAsUploaded(string $episodeUuid): bool;
}
