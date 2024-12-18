<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Saga;
use App\Models\Season;
use Illuminate\Console\Command;
use League\Csv\Exception;
use League\Csv\Reader;

class ImportCsvData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {--file= : Le nom du fichier CSV à importer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importation des données depuis un fichier CSV';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        // Récupérer le fichier CSV à partir de l'option donnée
        $file = $this->option('file');

        if (!$file || !file_exists(storage_path("app/csv/$file"))) {
            $this->error('Le fichier CSV spécifié n\'existe pas.');
            return;
        }

        // Lire le fichier CSV
        $csv = Reader::createFromPath(storage_path("app/csv/$file"), 'r');
        $csv->setHeaderOffset(0); // La première ligne contient les en-têtes
        $csv->setDelimiter(';'); // Utiliser le point-virgule comme séparateur (si nécessaire)

        // Déterminer quel fichier est utilisé et quel type de contenu importer
        if (str_contains($file, 'series')) {
            $this->importSeries($csv);
        } elseif (str_contains($file, 'episodes')) {
            $this->importEpisodes($csv);
        } elseif (str_contains($file, 'movies')) {
            $this->importMovies($csv);
        }
    }

    /**
     * Importation des films à partir du fichier CSV.
     */
    protected function importMovies(Reader $csv): void
    {
        foreach ($csv as $record) {
            // Rechercher ou créer la saga si elle est spécifiée
            $saga = null; // Initialiser à null si aucune saga n'est spécifiée

            if (!empty($record['saga'])) {
                $saga = Saga::firstOrCreate(
                    ['name' => $record['saga']], // Condition de recherche
                    ['name' => $record['saga']]  // Valeur à insérer si la saga n'existe pas
                );
            }

            // Vérifie si un film avec le même titre existe déjà
            $movieExists = Content::where('title', $record['title'])->exists();

            if ($movieExists) {
                $this->info("Le film '{$record['title']}' existe déjà, passage au suivant.");
                continue; // Passer au film suivant
            }

            // Si le film n'existe pas, créer un nouvel enregistrement
            $content = Content::create([
                'title' => $record['title'],
                'description' => $record['description'],
                'release_date' => $record['release_date'],
                'imdb_rating' => $record['imdb_rating'],
                'type' => $record['type'] ?? 'Movie',
                'country' => $record['country'],
                'saga_id' => $saga ? $saga->id : null, // Associe l'ID de la saga si présente
                'actors' => $record['actors'],
            ]);
            // Associer les genres
            $this->attachGenres($content, $record['genres'] ?? '');
        }

        $this->info("Importation des films terminée.");
    }

    /**
     * Importation des séries à partir du fichier CSV.
     */
    protected function importSeries(Reader $csv): void
    {
        foreach ($csv as $record) {
            // Créer ou mettre à jour la série
            $content = Content::updateOrCreate(
                ['title' => $record['title']], // Utiliser le titre pour vérifier l'existence
                [
                    'description' => $record['description'],
                    'release_date' => $record['release_date'],
                    'imdb_rating' => $record['imdb_rating'],
                    'type' => $record['type'] ?? 'Series',
                    'country' => $record['country'],
                    'actors' => $record['actors'],
                ]
            );

            // Associer les genres
            $this->attachGenres($content, $record['genres'] ?? '');
        }

        $this->info("Importation des séries terminée.");
    }

    /**
     * Importation des épisodes à partir du fichier CSV.
     */
    protected function importEpisodes(Reader $csv): void
    {
        foreach ($csv as $record) {
            // Trouver ou créer la série par titre
            $series = Content::firstOrCreate(
                ['title' => $record['series_title']],
                [
                    'description' => $record['series_description'] ?? 'No description available',
                    'release_date' => $record['series_release_date'] ?? null,
                    'imdb_rating' => $record['series_imdb_rating'] ?? null,
                    'type' => 'Series',
                    'country' => $record['series_country'] ?? 'Unknown',
                ]
            );

            // Trouver ou créer la saison liée à la série
            $season = Season::firstOrCreate(
                [
                    'content_id' => $series->id,
                    'season_number' => $record['season_number'],
                    'total_episodes' => null
                ]
            );

            // Importer chaque épisode lié à la saison
            Episode::updateOrCreate(
                [
                    'season_id' => $season->id,
                    'episode_number' => $record['episode_number'],
                ],
                [
                    'title' => $record['episode_title'],
                    'description' => $record['description'],
                    'release_date' => $record['release_date'],
                    'imdb_rating' => $record['imdb_rating'],
                ]
            );
        }

        $this->info("Importation des épisodes terminée.");
    }

    /**
     * Associer les genres à un contenu.
     */
    protected function attachGenres(Content $content, string $genres): void
    {
        if (empty($genres)) {
            return;
        }

        $genreNames = explode(',', $genres);
        foreach ($genreNames as $genreName) {
            $genre = Genre::firstOrCreate(['name' => trim($genreName)]);
            $content->genres()->syncWithoutDetaching([$genre->id]); // Associer sans détacher les anciens genres
        }
    }

}
