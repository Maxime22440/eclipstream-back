<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un utilisateur Super Admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Récupérer l'email et le mot de passe depuis le fichier .env
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (!$email || !$password) {
            $this->error('Veuillez définir SUPER_ADMIN_EMAIL et SUPER_ADMIN_PASSWORD dans le fichier .env');
            return 1;
        }

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error('Un utilisateur avec cet email existe déjà.');
            return 1;
        }

        // Créer le Super Admin
        User::create([
            'username' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password), // Hachage sécurisé du mot de passe
            'is_admin' => true,
        ]);

        $this->info('Super Admin créé avec succès!');
        return 0;
    }
}
