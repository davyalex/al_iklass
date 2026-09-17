<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Rôles par défaut de l'application : ni renommables, ni supprimables depuis l'interface.
     *
     * @var list<string>
     */
    public const ROLES_PROTEGES = [
        'superadmin',
        'admin',
        'gestionnaire',
        'gestionnaire_stock',
        'chef_mecanicien',
    ];

    /**
     * @param  array{name: string, permissions?: list<string>}  $data
     */
    public function creer(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

            $role->syncPermissions($data['permissions'] ?? []);

            activity()
                ->performedOn($role)
                ->withProperties(['permissions' => $role->permissions->pluck('name')])
                ->log("Rôle « {$role->name} » créé.");

            return $role;
        });
    }

    /**
     * @param  list<string>  $permissions
     */
    public function mettreAJourPermissions(Role $role, array $permissions): Role
    {
        if ($role->name === 'superadmin') {
            throw ValidationException::withMessages([
                'permissions' => "Le rôle superadmin a un accès total et ne se configure pas.",
            ]);
        }

        return DB::transaction(function () use ($role, $permissions) {
            $role->syncPermissions($permissions);

            activity()
                ->performedOn($role)
                ->withProperties(['permissions' => $permissions])
                ->log("Permissions du rôle « {$role->name} » mises à jour.");

            return $role;
        });
    }

    public function supprimer(Role $role): void
    {
        if (in_array($role->name, self::ROLES_PROTEGES, true)) {
            throw ValidationException::withMessages([
                'role' => "Le rôle « {$role->name} » est un rôle par défaut de l'application, il ne peut pas être supprimé.",
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => "Le rôle « {$role->name} » est encore attribué à des utilisateurs, il ne peut pas être supprimé.",
            ]);
        }

        $nom = $role->name;

        $role->delete();

        activity()->log("Rôle « {$nom} » supprimé.");
    }
}
