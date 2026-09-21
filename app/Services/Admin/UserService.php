<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @param  array{
     *     name: string,
     *     username: string,
     *     email?: ?string,
     *     telephone: string,
     *     role: string,
     * }  $data
     * @return array{user: User, password: string}
     */
    public function creer(array $data): array
    {
        $password = $this->genererMotDePasse();

        $user = DB::transaction(function () use ($data, $password) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'],
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $user->syncRoles([$data['role']]);

            activity()
                ->performedOn($user)
                ->withProperties(['role' => $data['role']])
                ->log("Compte « {$user->name} » créé avec le rôle {$data['role']}.");

            return $user;
        });

        return ['user' => $user, 'password' => $password];
    }

    /**
     * @param  array{
     *     name: string,
     *     username: string,
     *     email?: ?string,
     *     telephone: string,
     *     role: string,
     * }  $data
     */
    public function mettreAJour(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $ancienRole = $user->roles->pluck('name')->first();

            $user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'],
            ]);

            $user->syncRoles([$data['role']]);

            activity()
                ->performedOn($user)
                ->withProperties(['role_avant' => $ancienRole, 'role_apres' => $data['role']])
                ->log("Compte « {$user->name} » mis à jour.");

            return $user;
        });
    }

    /**
     * @return array{user: User, password: string}
     */
    public function reinitialiserMotDePasse(User $user): array
    {
        $password = $this->genererMotDePasse();

        $user->forceFill([
            'password' => Hash::make($password),
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ])->save();

        activity()->performedOn($user)->log("Mot de passe de « {$user->name} » réinitialisé.");

        return ['user' => $user, 'password' => $password];
    }

    public function activer(User $user): User
    {
        $user->forceFill(['is_active' => true])->save();

        activity()->performedOn($user)->log("Compte « {$user->name} » activé.");

        return $user;
    }

    public function desactiver(User $user): User
    {
        $user->forceFill(['is_active' => false])->save();

        activity()->performedOn($user)->log("Compte « {$user->name} » désactivé.");

        return $user;
    }

    public function supprimer(User $user): void
    {
        activity()->performedOn($user)->log("Compte « {$user->name} » archivé.");

        $user->delete();
    }

    private function genererMotDePasse(): string
    {
        return str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }
}
