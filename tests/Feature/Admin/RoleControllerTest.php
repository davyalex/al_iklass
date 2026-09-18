<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_roles_index(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.roles.index'))
            ->assertOk()
            ->assertViewIs('admin.roles.index');
    }

    public function test_gestionnaire_cannot_view_roles_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_admin_can_create_custom_role_with_permissions(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.roles.store'), [
            'name' => 'comptable',
            'permissions' => ['stock.tableau_bord.voir', 'stock.paiement.gerer'],
        ]);

        $response->assertCreated();

        $role = Role::where('name', 'comptable')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('stock.tableau_bord.voir'));
        $this->assertTrue($role->hasPermissionTo('stock.paiement.gerer'));
        $this->assertFalse($role->hasPermissionTo('stock.achat.gerer'));
    }

    public function test_cannot_create_role_with_duplicate_name(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->postJson(route('admin.roles.store'), [
            'name' => 'admin',
        ])->assertUnprocessable();
    }

    public function test_admin_can_update_permissions_of_custom_role(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $role = Role::create(['name' => 'comptable', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->putJson(route('admin.roles.update-permissions', $role), [
            'permissions' => ['stock.paiement.gerer'],
        ]);

        $response->assertOk();
        $this->assertTrue($role->fresh()->hasPermissionTo('stock.paiement.gerer'));
    }

    public function test_cannot_update_permissions_of_superadmin_role(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $superadmin = Role::where('name', 'superadmin')->firstOrFail();

        $this->actingAs($admin)->putJson(route('admin.roles.update-permissions', $superadmin), [
            'permissions' => ['stock.tableau_bord.voir'],
        ])->assertStatus(422);
    }

    public function test_cannot_delete_protected_default_role(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $chefMecanicien = Role::where('name', 'chef_mecanicien')->firstOrFail();

        $this->actingAs($admin)->deleteJson(route('admin.roles.destroy', $chefMecanicien))
            ->assertStatus(422);

        $this->assertDatabaseHas('roles', ['name' => 'chef_mecanicien']);
    }

    public function test_cannot_delete_role_still_assigned_to_users(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $role = Role::create(['name' => 'comptable', 'guard_name' => 'web']);
        User::factory()->create()->assignRole('comptable');

        $this->actingAs($admin)->deleteJson(route('admin.roles.destroy', $role))
            ->assertStatus(422);

        $this->assertDatabaseHas('roles', ['name' => 'comptable']);
    }

    public function test_can_delete_unused_custom_role(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $role = Role::create(['name' => 'comptable', 'guard_name' => 'web']);

        $this->actingAs($admin)->deleteJson(route('admin.roles.destroy', $role))
            ->assertOk();

        $this->assertDatabaseMissing('roles', ['name' => 'comptable']);
    }
}
