<?php

namespace App\Repositories\Interfaces;

use App\Models\Content;
use App\Models\Season;
use Illuminate\Support\Collection;

interface ContentRepositoryInterface
{
    public function addContent(array $data, string $type): Content;
    public function getAllSeries(): Collection;
    public function getClassicSeries(): Collection;
    public function getAnimeSeries(): Collection;
    public function addSeasonToSeries(string $seriesId, array $seasonData): Season;
    public function getSeasonsBySeriesId(string $seriesId);
    public function getContentByUuid(string $uuid): ?Content;
}
