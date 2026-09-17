<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Admin\UserService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_audit_log(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk();
        $this->actingAs($admin)->getJson(route('admin.audit.data'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal']);
    }

    public function test_gestionnaire_cannot_view_audit_log(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_creating_a_user_is_recorded_in_the_audit_log(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin);

        app(UserService::class)->creer([
            'name' => 'Nouvel Utilisateur',
            'username' => 'nouveluser',
            'telephone' => '0102030405',
            'role' => 'gestionnaire',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.audit.data'));

        $response->assertOk();
        $descriptions = collect($response->json('data'))->pluck('description');
        $this->assertTrue($descriptions->contains(fn ($d) => str_contains($d, 'Nouvel Utilisateur')));
    }
}
