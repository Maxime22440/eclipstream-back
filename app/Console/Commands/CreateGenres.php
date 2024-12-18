<?php

namespace App\Console\Commands;

use App\Models\Genre;
use Illuminate\Console\Command;

class CreateGenres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:genres';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée les genres par défaut dans la base de données';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Liste des genres à ajouter
        $genres = [
            'Action',
            'Aventure',
            'Animation',
            'Biopic',
            'Comédie',
            'Comédie dramatique',
            'Comédie romantique',
            'Crime',
            'Documentaire',
            'Drame',
            'Espionnage',
            'Fantastique',
            'Film noir',
            'Guerre',
            'Horreur',
            'Historique',
            'Musical',
            'Mystère',
            'Policier',
            'Romance',
            'Science-fiction',
            'Super-héros',
            'Suspense',
            'Western',
            'Zombie',
            'Famille',
            'Enfant',
            'Adolescent',
            'Psychologique',
            'Surnaturel',
            'Sport',
            'Erotique',
            'Médical',
            'Judiciaire',
            'Politique',
            'Survival',
            'Fantasy urbaine',
            'Cyberpunk',
            'Steampunk',
            'Dark fantasy',
            'Post-apocalyptique',
            'Uchronie',
            'Thriller',
            'Épouvante-horreur',

            // Genres spécifiques à l'animation et aux animés
            'Shonen',
            'Shojo',
            'Seinen',
            'Josei',
            'Mecha',
            'Isekai',
            'Slice of Life',
            'Magical Girl',
            'Harem',
            'Ecchi',
            'Yuri',
            'Yaoi',
            'Hentai',
            'Psychologique',
            'Samurai',
            'Gore',
            'Space Opera',

            // Genres de niche
            'Noël',
            'Culinaire',
            'True crime',
            'Film catastrophe',
            'Road movie',
            'Science-fiction militaire',
            'Exploration spatiale',
            'Wuxia',
            'Kaiju',
        ];

        foreach ($genres as $genre) {
            Genre::firstOrCreate(['name' => $genre]);
        }

        $this->info('Genres créés avec succès !');
        return 0;
    }
}
