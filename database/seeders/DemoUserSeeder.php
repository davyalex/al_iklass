<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['name' => 'Admin Démo', 'username' => 'admin.demo', 'telephone' => '0100000001', 'role' => 'admin'],
            ['name' => 'Gestionnaire Démo', 'username' => 'gestionnaire.demo', 'telephone' => '0100000002', 'role' => 'gestionnaire'],
            ['name' => 'Gestionnaire Stock Démo', 'username' => 'gestionnaire.stock.demo', 'telephone' => '0100000003', 'role' => 'gestionnaire_stock'],
            ['name' => 'Chef Mécanicien Démo', 'username' => 'chef.mecanicien.demo', 'telephone' => '0100000004', 'role' => 'chef_mecanicien'],
        ];

        // Par défaut, 10 gestionnaires au total (le compte démo ci-dessus + 9),
        // pour peupler la page Flotte > Gestionnaires.
        foreach ([
            'Yao Koffi', 'Aya Bamba', 'Adjoua Kouassi', 'Kouamé Diallo', 'Fatou Diabaté',
            'Kader Touré', 'Awa Coulibaly', 'Moussa Sangaré', 'Aminata Konaté',
        ] as $index => $nom) {
            $numero = $index + 2;

            $comptes[] = [
                'name' => $nom,
                'username' => strtolower(str_replace(' ', '.', $nom)),
                'telephone' => '0200000'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'role' => 'gestionnaire',
            ];
        }

        foreach ($comptes as $compte) {
            if (User::where('username', $compte['username'])->exists()) {
                continue;
            }

            $resultat = app(UserService::class)->creer($compte);

            $this->command?->warn("Compte {$compte['role']} créé : {$resultat['user']->username}");
            $this->command?->warn("Mot de passe temporaire : {$resultat['password']}");
        }
    }
}
