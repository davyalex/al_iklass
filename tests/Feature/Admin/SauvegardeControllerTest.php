<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Admin\SauvegardeService;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SauvegardeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ParametreSeeder::class);
    }

    public function test_admin_can_trigger_a_manual_backup(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->mock(SauvegardeService::class, function ($mock) {
            $mock->shouldReceive('creer')->once()->andReturn('al-iklass_2026-01-01_020000.sql.gz');
            $mock->shouldReceive('purger')->once()->andReturn(0);
        });

        $this->actingAs($admin)->postJson(route('admin.parametres.sauvegardes.creer'))->assertOk();
    }

    public function test_gestionnaire_cannot_trigger_a_manual_backup(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->postJson(route('admin.parametres.sauvegardes.creer'))->assertForbidden();
    }

    public function test_admin_can_download_a_backup(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $fichier = tempnam(sys_get_temp_dir(), 'aliklass_test_');
        file_put_contents($fichier, 'contenu-factice');

        $this->mock(SauvegardeService::class, function ($mock) use ($fichier) {
            $mock->shouldReceive('cheminSecurise')->with('al-iklass_2026-01-01_020000.sql.gz')->once()->andReturn($fichier);
        });

        $this->actingAs($admin)
            ->get(route('admin.parametres.sauvegardes.telecharger', 'al-iklass_2026-01-01_020000.sql.gz'))
            ->assertOk();

        @unlink($fichier);
    }

    public function test_superadmin_can_restore_a_backup(): void
    {
        $superadmin = User::factory()->create()->assignRole('superadmin');

        $this->mock(SauvegardeService::class, function ($mock) {
            $mock->shouldReceive('restaurer')->with('al-iklass_2026-01-01_020000.sql.gz')->once();
        });

        $this->actingAs($superadmin)
            ->postJson(route('admin.parametres.sauvegardes.restaurer', 'al-iklass_2026-01-01_020000.sql.gz'))
            ->assertOk();
    }

    public function test_admin_cannot_restore_a_backup_even_with_gerer_permission(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->postJson(route('admin.parametres.sauvegardes.restaurer', 'al-iklass_2026-01-01_020000.sql.gz'))
            ->assertForbidden();
    }
}
