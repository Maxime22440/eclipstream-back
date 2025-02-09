<?php

namespace App\Repositories;

use App\Models\Episode;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\EpisodeRepositoryInterface;

class EpisodeRepository implements EpisodeRepositoryInterface
{
    /**
     * Récupère tous les épisodes associés à une saison donnée.
     *
     * @param string $seasonId L'identifiant de la saison.
     *
     * @return Collection Collection d'épisodes.
     */
    public function getEpisodesBySeasonId(string $seasonId): Collection
    {
        return Episode::where('season_id', $seasonId)->get();
    }

    /**
     * Ajoute un nouvel épisode à une saison donnée.
     *
     * Vérifie d'abord qu'il n'existe pas déjà un épisode avec le même numéro dans la saison.
     *
     * @param string $seasonId L'identifiant de la saison.
     * @param array  $episodeData Données de l'épisode, incluant episode_number, title, description, release_date, imdb_rating, duration.
     *
     * @return Episode L'épisode créé.
     *
     * @throws Exception Si un épisode avec le même numéro existe déjà ou en cas d'erreur d'enregistrement.
     */
    public function addEpisodeToSeason(string $seasonId, array $episodeData): Episode
    {
        // Vérifie qu'il n'existe pas déjà un épisode avec ce numéro pour cette saison
        $existingEpisode = Episode::where('season_id', $seasonId)
            ->where('episode_number', $episodeData['episode_number'])
            ->first();

        if ($existingEpisode) {
            throw new Exception("L'épisode numéro {$episodeData['episode_number']} existe déjà pour cette saison.");
        }

        // Création d'une nouvelle instance d'Episode
        $episode = new Episode();
        $episode->season_id = $seasonId;
        $episode->episode_number = $episodeData['episode_number'];
        $episode->title = $episodeData['title'];
        $episode->description = $episodeData['description'];
        $episode->release_date = $episodeData['release_date'];
        $episode->imdb_rating = $episodeData['imdb_rating'];
        $episode->duration = $episodeData['duration'];

        // Sauvegarde dans la base de données
        $episode->save();

        Log::info("Épisode ajouté avec succès pour la saison {$seasonId}.", ['episode_id' => $episode->id]);

        return $episode;
    }

    /**
     * Récupère tous les épisodes dont le champ is_uploaded est à 0.
     *
     * @return Collection Collection d'épisodes non uploadés.
     */
    public function getNotUploadedEpisodes(): Collection
    {
        return Episode::where('is_uploaded', 0)->get();
    }

    /**
     * Récupère un épisode par son UUID en chargeant également la saison et le contenu associé.
     *
     * @param string $episodeUuid L'UUID de l'épisode.
     *
     * @return Episode|null L'épisode avec ses relations, ou null s'il n'existe pas.
     */
    public function getEpisodeByUuid(string $episodeUuid): ?Episode
    {
        return Episode::where('uuid', $episodeUuid)
            ->with('season.content') // Charge la saison et, via la saison, le contenu associé
            ->first();
    }

    /**
     * Met à jour le champ is_uploaded de l'épisode identifié par son UUID.
     *
     * @param string $episodeUuid L'UUID de l'épisode.
     *
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function markEpisodeAsUploaded(string $episodeUuid): bool
    {
        $episode = Episode::where('uuid', $episodeUuid)->first();
        if (!$episode) {
            Log::warning("Tentative de marquage d'un épisode inexistant: {$episodeUuid}");
            return false;
        }
        $episode->is_uploaded = 1;
        return $episode->save();
    }
}
