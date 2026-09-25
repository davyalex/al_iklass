<?php

namespace Database\Seeders;

use App\Services\Admin\PermissionService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Crée les permissions et rôles définis dans config/permissions.php, et
     * resynchronise INTÉGRALEMENT les permissions de chaque rôle (écrase
     * toute personnalisation faite depuis Admin > Rôles). Réservé à une
     * installation fraîche ou à un déploiement qui doit explicitement
     * revenir aux permissions par défaut — sinon, utiliser la commande
     * `permissions:synchroniser` (additive, sans risque).
     */
    public function run(): void
    {
        $service = app(PermissionService::class);

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
                ->syncPermissions($service->resoudrePermissions($groupesDuRole, $groupesPermissions));
        }
    }
}
