<?php

namespace Tests\Feature\Admin;

use App\Models\Fournisseur;
use App\Models\User;
use App\Services\Admin\UserService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
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

    public function test_every_model_write_is_recorded_whoever_performs_it(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $this->actingAs($gestionnaireStock);

        $fournisseur = Fournisseur::factory()->create(['nom' => 'Garage Adjamé']);
        $fournisseur->update(['telephone' => '0101010101']);
        $fournisseur->delete();

        $entrees = Activity::where('subject_type', Fournisseur::class)->where('subject_id', $fournisseur->id)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted'], $entrees->pluck('event')->all());
        $this->assertTrue($entrees->every(fn (Activity $a) => $a->causer_id === $gestionnaireStock->id));
        $this->assertSame('Fournisseur « Garage Adjamé » archivé(e).', $entrees->last()->description);
        $this->assertSame(['telephone' => '0101010101'], $entrees[1]->properties['attributes']);
    }

    public function test_update_without_meaningful_change_is_not_recorded(): void
    {
        $fournisseur = Fournisseur::factory()->create();
        $avant = Activity::count();

        $fournisseur->touch();

        $this->assertSame($avant, Activity::count());
    }

    public function test_data_exposes_event_element_and_changes_and_can_be_filtered(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin);

        $fournisseur = Fournisseur::factory()->create(['telephone' => '0102030405']);
        $fournisseur->update(['telephone' => '0908070605']);

        $response = $this->getJson(route('admin.audit.data', [
            'evenement' => 'updated',
            'element' => Fournisseur::class,
        ]))->assertOk();

        $this->assertSame(1, $response->json('recordsFiltered'));
        $ligne = $response->json('data.0');
        $this->assertSame('Modification', $ligne['evenement']['libelle']);
        $this->assertSame('Fournisseur', $ligne['element']);
        $this->assertSame([['champ' => 'Telephone', 'avant' => '0102030405', 'apres' => '0908070605']], $ligne['changements']);

        $this->getJson(route('admin.audit.kpis'))
            ->assertOk()
            ->assertJsonStructure(['total', 'aujourdhui', 'utilisateurs']);
    }

    public function test_purge_command_removes_old_entries_and_keeps_recent_ones(): void
    {
        activity()->createdAt(now()->subDays(45))->log('Ancienne action');
        activity()->createdAt(now()->subDays(5))->log('Action récente');

        $this->artisan('audit:purger', ['--jours' => 30])->assertSuccessful();

        $descriptions = Activity::pluck('description');
        $this->assertFalse($descriptions->contains('Ancienne action'));
        $this->assertTrue($descriptions->contains('Action récente'));
        $this->assertSame(1, Activity::where('event', 'purge')->count());
    }

    public function test_purge_is_scheduled_monthly(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('audit:purger')
            ->assertSuccessful();
    }
}
