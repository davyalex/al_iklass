<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_profile_page_shows_account_information(): void
    {
        $user = User::factory()->create(['name' => 'Awa Koné'])->assignRole('gestionnaire');

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Awa Koné')
            ->assertSee($user->username)
            ->assertSee('Gestionnaire (parc)')
            ->assertSee($user->created_at->format('d/m/Y à H:i'))
            ->assertSee('Mon activité récente');
    }

    public function test_non_admin_profile_is_read_only(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('lecture seule')
            ->assertDontSee('Modifier mes informations')
            ->assertDontSee('Changer mon mot de passe');
    }

    public function test_admin_can_update_profile_information(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->patch(route('profile.update'), [
            'name' => 'Nouveau Nom',
            'username' => 'nouveau_admin',
            'email' => 'admin@example.com',
            'telephone' => '0707070707',
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertSame('Nouveau Nom', $admin->name);
        $this->assertSame('nouveau_admin', $admin->username);
        $this->assertSame('0707070707', $admin->telephone);

        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $admin->id,
            'subject_id' => $admin->id,
            'event' => 'updated',
        ]);
    }

    public function test_superadmin_can_update_profile_information(): void
    {
        $superadmin = User::factory()->create()->assignRole('superadmin');

        $this->actingAs($superadmin)->patch(route('profile.update'), [
            'name' => 'Développeur',
            'username' => 'dev_root',
            'telephone' => $superadmin->telephone,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Développeur', $superadmin->refresh()->name);
    }

    public function test_non_admin_roles_cannot_update_their_profile(): void
    {
        foreach (['gestionnaire', 'gestionnaire_stock', 'chef_mecanicien'] as $role) {
            $user = User::factory()->create(['name' => 'Nom Initial'])->assignRole($role);

            $this->actingAs($user)->patch(route('profile.update'), [
                'name' => 'Nom Modifié',
                'username' => $user->username,
                'telephone' => $user->telephone,
            ])->assertForbidden();

            $this->actingAs($user)->put(route('profile.password.update'), [
                'current_password' => '12345',
                'password' => 'nouveau-mdp',
                'password_confirmation' => 'nouveau-mdp',
            ])->assertForbidden();

            $this->assertSame('Nom Initial', $user->refresh()->name, "{$role} ne devrait pas pouvoir modifier son profil");
        }
    }

    public function test_admin_can_change_password_with_current_password(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->put(route('profile.password.update'), [
            'current_password' => '12345',
            'password' => 'nouveau-mdp',
            'password_confirmation' => 'nouveau-mdp',
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('nouveau-mdp', $admin->refresh()->password));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->put(route('profile.password.update'), [
            'current_password' => 'mauvais',
            'password' => 'nouveau-mdp',
            'password_confirmation' => 'nouveau-mdp',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('12345', $admin->refresh()->password));
    }

    public function test_login_records_last_login_date_and_audit_entry(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->post(route('login'), ['username' => $user->username, 'password' => '12345']);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->derniere_connexion_at);
        $this->assertSame(1, Activity::where('event', 'connexion')->where('causer_id', $user->id)->count());
    }
}
