<?php

namespace Tests\Feature\Admin;

use App\Models\Parametre;
use App\Models\User;
use App\Services\Admin\IdentiteApplicationService;
use Database\Seeders\ParametreSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentiteApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ParametreSeeder::class);
        Storage::fake('public');
    }

    private function televerserLogo(User $admin, int $largeur = 600, int $hauteur = 300): void
    {
        $this->actingAs($admin)->postJson(route('admin.parametres.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', $largeur, $hauteur),
        ])->assertOk();
    }

    public function test_upload_du_logo_genere_favicons_icones_et_logo_pdf(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->televerserLogo($admin);

        $disque = Storage::disk('public');
        foreach (['favicon-16.png', 'favicon-32.png', 'favicon-48.png', 'favicon.ico', 'apple-touch-icon.png', 'icone-192.png', 'icone-512.png', 'icone-maskable-512.png', 'logo-pdf.png'] as $fichier) {
            $disque->assertExists('icones/'.$fichier);
        }

        $apple = getimagesizefromstring($disque->get('icones/apple-touch-icon.png'));
        $this->assertSame([180, 180], [$apple[0], $apple[1]]);

        // .ico valide : en-tête « icône » (type 1) contenant les 3 tailles
        $ico = unpack('vreserve/vtype/vnombre', substr($disque->get('icones/favicon.ico'), 0, 6));
        $this->assertSame(['reserve' => 0, 'type' => 1, 'nombre' => 3], $ico);
    }

    public function test_reponse_d_upload_contient_les_urls_d_icones_versionnees(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.parametres.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertOk()->assertJsonStructure(['url', 'icones' => ['favicon', 'apple', 'sidebar']]);
        $this->assertStringContainsString('icones/favicon-32.png?v=', $response->json('icones.favicon'));
    }

    public function test_remplacer_le_logo_supprime_l_ancien_fichier(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->televerserLogo($admin);
        $ancienChemin = Parametre::valeur('application.logo');
        $this->televerserLogo($admin);

        Storage::disk('public')->assertMissing($ancienChemin);
        Storage::disk('public')->assertExists(Parametre::valeur('application.logo'));
    }

    public function test_logo_trop_petit_est_refuse(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->postJson(route('admin.parametres.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 32, 32),
        ])->assertUnprocessable()->assertJsonValidationErrors('logo');
    }

    public function test_admin_peut_retirer_le_logo(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->televerserLogo($admin);
        $chemin = Parametre::valeur('application.logo');

        $this->actingAs($admin)->deleteJson(route('admin.parametres.logo.retirer'))
            ->assertOk()
            ->assertJsonPath('url', null);

        $this->assertSame('', Parametre::valeur('application.logo'));
        Storage::disk('public')->assertMissing($chemin);
        Storage::disk('public')->assertMissing('icones/favicon-32.png');
    }

    public function test_gestionnaire_ne_peut_pas_retirer_le_logo(): void
    {
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($gestionnaire)->deleteJson(route('admin.parametres.logo.retirer'))->assertForbidden();
    }

    public function test_page_de_connexion_affiche_nom_et_logo_parametres(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Parametre::where('cle', 'application.nom')->firstOrFail()->update(['valeur' => 'Transports Kouassi']);
        $this->televerserLogo($admin);
        auth()->logout();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Transports Kouassi')
            ->assertSee('<title>Transports Kouassi</title>', false)
            ->assertSee(Parametre::valeur('application.logo'))
            ->assertSee('icones/favicon-32.png?v=', false)
            ->assertSee('rel="manifest"', false);
    }

    public function test_sans_logo_les_icones_par_defaut_sont_utilisees(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('AL-IKLASS')
            ->assertSee(asset('icones/favicon.svg'), false)
            ->assertSee(asset('icones/apple-touch-icon.png'), false);
    }

    public function test_changement_de_nom_est_repercute_malgre_le_cache(): void
    {
        $identite = app(IdentiteApplicationService::class);
        $this->assertSame('AL-IKLASS', $identite->nom());

        Parametre::where('cle', 'application.nom')->firstOrFail()->update(['valeur' => 'Nouveau Nom']);

        $this->assertSame('Nouveau Nom', $identite->nom());
    }

    public function test_manifeste_web_utilise_le_nom_et_les_icones(): void
    {
        $response = $this->get(route('manifest'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'AL-IKLASS')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonCount(3, 'icons');
    }

    public function test_logo_pdf_disponible_en_data_uri_apres_upload(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $identite = app(IdentiteApplicationService::class);
        $this->assertNull($identite->logoPdfDataUri());

        $this->televerserLogo($admin);

        $this->assertStringStartsWith('data:image/png;base64,', $identite->logoPdfDataUri());
    }

    public function test_commande_regenere_les_icones_depuis_le_logo_actuel(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->televerserLogo($admin);
        Storage::disk('public')->deleteDirectory('icones');

        $this->artisan('application:regenerer-icones')->assertSuccessful();

        Storage::disk('public')->assertExists('icones/favicon.ico');
    }
}
