<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Crée les permissions et rôles définis dans config/permissions.php, et
     * synchronise les permissions de chaque rôle. Rejouable sans risque : à
     * exécuter chaque fois qu'une permission est ajoutée dans la config.
     */
    public function run(): void
    {
        /** @var array<string, list<string>> $groupesPermissions */
        $groupesPermissions = config('permissions.permissions');

        foreach ($groupesPermissions as $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
        }

        /** @var array<string, array<int|string, mixed>> $roles */
        $roles = config('permissions.roles');

        foreach ($roles as $role => $groupesDuRole) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($this->resoudrePermissions($groupesDuRole, $groupesPermissions));
        }
    }

    /**
     * Résout la configuration d'un rôle (liste de groupes entiers, ou
     * groupes filtrés via "only"/"except") en une liste plate de noms de
     * permissions.
     *
     * @param  array<int|string, mixed>  $groupesDuRole
     * @param  array<string, list<string>>  $groupesPermissions
     * @return list<string>
     */
    private function resoudrePermissions(array $groupesDuRole, array $groupesPermissions): array
    {
        $permissions = [];

        foreach ($groupesDuRole as $cle => $valeur) {
            if (is_int($cle)) {
                // Entrée simple, ex. 'stock' : le groupe entier est assigné.
                $permissions = [...$permissions, ...($groupesPermissions[$valeur] ?? [])];

                continue;
            }

            // Entrée filtrée, ex. 'stock' => ['except' => [...]] ou ['only' => [...]].
            $groupe = $groupesPermissions[$cle] ?? [];

            $permissions = match (true) {
                isset($valeur['only']) => [...$permissions, ...$valeur['only']],
                isset($valeur['except']) => [...$permissions, ...array_diff($groupe, $valeur['except'])],
                default => [...$permissions, ...$groupe],
            };
        }

        return array_values(array_unique($permissions));
    }
}
