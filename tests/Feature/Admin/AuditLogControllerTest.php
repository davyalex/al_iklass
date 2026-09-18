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

    public function test_data_can_be_filtered_by_causer(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $autreAdmin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin);
        app(UserService::class)->creer([
            'name' => 'Utilisateur Filtré',
            'username' => 'utilisateurfiltre',
            'telephone' => '0102030406',
            'role' => 'gestionnaire',
        ]);

        $this->actingAs($autreAdmin);
        app(UserService::class)->creer([
            'name' => 'Autre Utilisateur',
            'username' => 'autreutilisateur',
            'telephone' => '0102030407',
            'role' => 'gestionnaire',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.audit.data', [
            'causer_id' => $admin->id,
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_admin_can_export_audit_log_excel_and_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.audit.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->get(route('admin.audit.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_gestionnaire_cannot_export_audit_log(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)->get(route('admin.audit.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.audit.export.pdf'))->assertForbidden();
    }
}
