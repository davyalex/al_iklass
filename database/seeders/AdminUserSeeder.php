<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('admin.superadmin');

        $utilisateurExistant = User::where('username', $config['username'])
            ->orWhere('email', $config['email'])
            ->first();

        if ($utilisateurExistant) {
            $this->resynchroniser($utilisateurExistant, $config);

            return;
        }

        $this->creer($config);
    }

    /**
     * @param  array{name: string, username: string, email: ?string, telephone: string, password: ?string}  $config
     */
    private function resynchroniser(User $utilisateur, array $config): void
    {
        $utilisateur->forceFill([
            'name' => $config['name'],
            'username' => $config['username'],
            'email' => $config['email'],
            'telephone' => $config['telephone'],
            'is_active' => true,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);

        if ($config['password']) {
            $utilisateur->password = Hash::make($config['password']);
        }

        $utilisateur->save();

        if (! $utilisateur->hasRole('superadmin')) {
            $utilisateur->assignRole('superadmin');
        }

        $this->command?->warn("Compte superadmin resynchronisé depuis .env : {$utilisateur->username}");

        if ($config['password']) {
            $this->command?->warn("Mot de passe (SUPERADMIN_PASS) : {$config['password']}");
        }
    }

    /**
     * @param  array{name: string, username: string, email: ?string, telephone: string, password: ?string}  $config
     */
    private function creer(array $config): void
    {
        if ($config['password']) {
            $user = User::create([
                'name' => $config['name'],
                'username' => $config['username'],
                'email' => $config['email'],
                'telephone' => $config['telephone'],
                'password' => Hash::make($config['password']),
                'is_active' => true,
            ]);

            $user->assignRole('superadmin');

            $this->command?->warn("Compte superadmin créé depuis .env : {$user->username}");
            $this->command?->warn("Mot de passe (SUPERADMIN_PASS) : {$config['password']}");

            return;
        }

        $resultat = app(UserService::class)->creer([
            'name' => $config['name'],
            'username' => $config['username'],
            'email' => $config['email'],
            'telephone' => $config['telephone'],
            'role' => 'superadmin',
        ]);

        $this->command?->warn("Compte superadmin créé : {$resultat['user']->username}");
        $this->command?->warn("Mot de passe temporaire : {$resultat['password']}");
        $this->command?->warn('Notez-le maintenant, il ne sera plus jamais affiché. Astuce : définissez SUPERADMIN_PASS dans .env pour fixer ce mot de passe.');
    }
}
