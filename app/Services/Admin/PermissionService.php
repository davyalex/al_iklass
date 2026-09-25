<?php

namespace App\Services\Admin;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    /**
     * Crée les permissions manquantes et attribue aux rôles celles qui leur
     * manquent d'après config/permissions.php — sans jamais retirer une
     * permission déjà attribuée. Contrairement à RolePermissionSeeder (qui
     * resynchronise intégralement chaque rôle et écrase donc les
     * personnalisations faites depuis Admin > Rôles), cette méthode est sans
     * risque à lancer à chaque déploiement, y compris en routine.
     *
     * @return array{permissions_creees: int, attributions_ajoutees: int}
     */
    public function synchroniserDeFaconAdditive(): array
    {
        /** @var array<string, list<string>> $groupesPermissions */
        $groupesPermissions = config('permissions.permissions');

        $permissionsCreees = 0;

        foreach ($groupesPermissions as $permissions) {
            foreach ($permissions as $permission) {
                $creee = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

                if ($creee->wasRecentlyCreated) {
                    $permissionsCreees++;
                }
            }
        }

        /** @var array<string, array<int|string, mixed>> $rolesConfig */
        $rolesConfig = config('permissions.roles');

        $attributionsAjoutees = 0;

        foreach ($rolesConfig as $nomRole => $groupesDuRole) {
            $role = Role::firstOrCreate(['name' => $nomRole, 'guard_name' => 'web']);

            $permissionsAttendues = $this->resoudrePermissions($groupesDuRole, $groupesPermissions);
            $permissionsActuelles = $role->permissions()->pluck('name')->all();
            $manquantes = array_diff($permissionsAttendues, $permissionsActuelles);

            if ($manquantes !== []) {
                $role->givePermissionTo($manquantes);
                $attributionsAjoutees += count($manquantes);
            }
        }

        return ['permissions_creees' => $permissionsCreees, 'attributions_ajoutees' => $attributionsAjoutees];
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
    public function resoudrePermissions(array $groupesDuRole, array $groupesPermissions): array
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
