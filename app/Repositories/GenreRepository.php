<?php

namespace App\Repositories;

use App\Models\Genre;
use App\Repositories\Interfaces\GenreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GenreRepository implements GenreRepositoryInterface
{
    /**
     * Récupère tous les genres disponibles.
     *
     * @return Collection Collection d'instances de Genre.
     */
    public function getAllGenres(): Collection
    {
        return Genre::all();
    }
}
