<?php

namespace Tests\Feature\Admin;

use App\Models\Parametre;
use App\Models\User;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParametreControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ParametreSeeder::class);
    }

    public function test_admin_can_view_parametres_index(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.parametres.index'))->assertOk();
    }

    public function test_gestionnaire_cannot_view_parametres_index(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->get(route('admin.parametres.index'))->assertForbidden();
    }

    public function test_admin_can_update_a_parametre(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $parametre = Parametre::where('cle', 'flotte.statut_journalier.heure_fin_fenetre')->firstOrFail();

        $response = $this->actingAs($admin)->putJson(route('admin.parametres.update', $parametre), [
            'valeur' => '13:30',
        ]);

        $response->assertOk();
        $this->assertSame('13:30', $parametre->fresh()->valeur);
    }

    public function test_heure_parametre_doit_respecter_le_format_hh_mm(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $parametre = Parametre::where('cle', 'flotte.statut_journalier.heure_fin_fenetre')->firstOrFail();

        $response = $this->actingAs($admin)->putJson(route('admin.parametres.update', $parametre), [
            'valeur' => 'pas une heure',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('valeur');
    }

    public function test_admin_can_upload_a_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create()->assignRole('admin');
        $fichier = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->actingAs($admin)->postJson(route('admin.parametres.logo'), [
            'logo' => $fichier,
        ]);

        $response->assertOk();
        $parametre = Parametre::where('cle', 'application.logo')->firstOrFail();
        Storage::disk('public')->assertExists($parametre->valeur);
    }

    public function test_logo_must_be_an_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create()->assignRole('admin');
        $fichier = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($admin)->postJson(route('admin.parametres.logo'), [
            'logo' => $fichier,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('logo');
    }

    public function test_logo_rejects_svg_to_prevent_stored_xss(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create()->assignRole('admin');
        $fichier = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

        $response = $this->actingAs($admin)->postJson(route('admin.parametres.logo'), [
            'logo' => $fichier,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('logo');
    }

    public function test_gestionnaire_cannot_upload_a_logo(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $fichier = UploadedFile::fake()->image('logo.png');

        $this->actingAs($gestionnaire)->postJson(route('admin.parametres.logo'), [
            'logo' => $fichier,
        ])->assertForbidden();
    }
}
