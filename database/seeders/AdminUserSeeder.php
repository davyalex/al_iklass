<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'alexkouamelan96@gmail.com';

        if (User::where('email', $email)->exists()) {
            return;
        }

        $resultat = app(UserService::class)->creer([
            'name' => 'Alex Kouamelan',
            'username' => 'superadmin',
            'email' => $email,
            'telephone' => '0000000000',
            'role' => 'superadmin',
        ]);

        $this->command?->warn("Compte superadmin créé : {$resultat['user']->username}");
        $this->command?->warn("Mot de passe temporaire : {$resultat['password']}");
        $this->command?->warn('Notez-le maintenant, il ne sera plus jamais affiché.');
    }
}
