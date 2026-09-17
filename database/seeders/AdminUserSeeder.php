<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'alexkouamelan96@gmail.com';

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = Str::password(16);

        $user = User::create([
            'name' => 'Alex Kouamelan',
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole('superadmin');

        $this->command?->warn("Compte superadmin créé : {$email}");
        $this->command?->warn("Mot de passe temporaire : {$password}");
        $this->command?->warn('Notez-le maintenant, il ne sera plus jamais affiché.');
    }
}
