<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions du module Stock (cahier des charges §2).
     *
     * @var list<string>
     */
    private const STOCK_PERMISSIONS = [
        'stock.dashboard.view',
        'stock.article.manage',
        'stock.fournisseur.manage',
        'stock.achat.manage',
        'stock.paiement.manage',
        'stock.sortie.interne',
        'stock.sortie.vente',
        'stock.demande.create',
    ];

    public function run(): void
    {
        foreach (self::STOCK_PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // superadmin passe par Gate::before (AppServiceProvider) : pas besoin de lui assigner de permissions.
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->syncPermissions(self::STOCK_PERMISSIONS);

        Role::firstOrCreate(['name' => 'gestionnaire_stock', 'guard_name' => 'web'])
            ->syncPermissions(array_diff(self::STOCK_PERMISSIONS, ['stock.demande.create']));

        Role::firstOrCreate(['name' => 'chef_mecanicien', 'guard_name' => 'web'])
            ->syncPermissions(['stock.demande.create']);

        // Le rôle "gestionnaire" (parc) n'a aucune permission côté module Stock.
        Role::firstOrCreate(['name' => 'gestionnaire', 'guard_name' => 'web']);
    }
}
