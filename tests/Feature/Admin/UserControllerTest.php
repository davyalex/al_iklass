<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_users_index_grouped_by_role(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'gestionnaire']))->assertOk();
    }

    public function test_index_filtered_by_role_only_shows_matching_users(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create(['name' => 'Awa Koné'])->assignRole('gestionnaire');
        $chefMecanicien = User::factory()->create(['name' => 'Yao Bamba'])->assignRole('chef_mecanicien');

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'gestionnaire']));

        $response->assertOk();
        $response->assertSee('Awa Koné');
        $response->assertDontSee('Yao Bamba');

        $usersParRole = $response->viewData('usersParRole');
        $this->assertTrue($usersParRole->has('gestionnaire'));
        $this->assertFalse($usersParRole->has('chef_mecanicien'));
    }

    public function test_gestionnaire_cannot_view_users_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_create_user_with_generated_password(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.users.store'), [
            'name' => 'Awa Koné',
            'username' => 'akone',
            'telephone' => '0102030405',
            'role' => 'gestionnaire',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'user', 'password']);

        $password = $response->json('password');
        $this->assertMatchesRegularExpression('/^\d{5}$/', $password);

        $user = User::where('username', 'akone')->firstOrFail();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->hasRole('gestionnaire'));
        $this->assertTrue($user->is_active);
    }

    public function test_gestionnaire_cannot_create_user(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)->postJson(route('admin.users.store'), [
            'name' => 'Test',
            'username' => 'test',
            'telephone' => '0102030405',
            'role' => 'gestionnaire',
        ])->assertForbidden();
    }

    public function test_telephone_must_be_ten_digits(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->postJson(route('admin.users.store'), [
            'name' => 'Test',
            'username' => 'test',
            'telephone' => '12345',
            'role' => 'gestionnaire',
        ])->assertJsonValidationErrors('telephone');
    }

    public function test_admin_can_reset_password(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.users.reset-password', $user));

        $response->assertOk();
        $password = $response->json('password');
        $this->assertMatchesRegularExpression('/^\d{5}$/', $password);
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_admin_can_deactivate_and_activate_user(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->create();

        $this->actingAs($admin)->patchJson(route('admin.users.deactivate', $user))->assertOk();
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)->patchJson(route('admin.users.activate', $user))->assertOk();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_deactivated_account_is_reactivated_and_unlocked_by_password_reset(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->locked()->create();

        $this->actingAs($admin)->postJson(route('admin.users.reset-password', $user))->assertOk();

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_at);
    }

    public function test_admin_can_delete_a_gestionnaire(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $response = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $gestionnaire));

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $gestionnaire->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $admin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_gestionnaire_cannot_delete_a_user(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $autre = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->deleteJson(route('admin.users.destroy', $autre))->assertForbidden();
    }
}
