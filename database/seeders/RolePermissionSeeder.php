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
        'stock.tableau_bord.voir',
        'stock.article.gerer',
        'stock.fournisseur.gerer',
        'stock.bon_commande.gerer',
        'stock.achat.gerer',
        'stock.paiement.gerer',
        'stock.sortie.interne',
        'stock.sortie.vente',
        'stock.demande.creer',
    ];

    /**
     * Permissions de gestion des utilisateurs.
     *
     * @var list<string>
     */
    private const USER_PERMISSIONS = [
        'utilisateurs.voir',
        'utilisateurs.gerer',
    ];

    /**
     * Permissions de gestion des rôles/permissions et du journal d'audit.
     *
     * @var list<string>
     */
    private const ADMIN_PERMISSIONS = [
        'roles.voir',
        'roles.gerer',
        'audit.voir',
        'unites.voir',
        'unites.gerer',
    ];

    public function run(): void
    {
        foreach ([...self::STOCK_PERMISSIONS, ...self::USER_PERMISSIONS, ...self::ADMIN_PERMISSIONS] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // superadmin passe par Gate::before (AppServiceProvider) : pas besoin de lui assigner de permissions.
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->syncPermissions([...self::STOCK_PERMISSIONS, ...self::USER_PERMISSIONS, ...self::ADMIN_PERMISSIONS]);

        Role::firstOrCreate(['name' => 'gestionnaire_stock', 'guard_name' => 'web'])
            ->syncPermissions([
                ...array_diff(self::STOCK_PERMISSIONS, ['stock.demande.creer']),
                'unites.voir',
                'unites.gerer',
            ]);

        Role::firstOrCreate(['name' => 'chef_mecanicien', 'guard_name' => 'web'])
            ->syncPermissions(['stock.demande.creer']);

        // Le rôle "gestionnaire" (parc) n'a aucune permission côté module Stock.
        Role::firstOrCreate(['name' => 'gestionnaire', 'guard_name' => 'web']);
    }
}
