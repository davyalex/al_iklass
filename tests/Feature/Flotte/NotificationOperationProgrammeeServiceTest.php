<?php

namespace Tests\Feature\Flotte;

use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\User;
use App\Models\Vehicule;
use App\Notifications\OperationProgrammeeRappelNotification;
use App\Services\Flotte\NotificationOperationProgrammeeService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeOperationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationOperationProgrammeeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypeOperationSeeder::class);
    }

    private function creerOperation(array $overrides = []): OperationProgrammee
    {
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        return OperationProgrammee::create(array_merge([
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id,
            'type_operation_code' => $vidange->code,
            'type_operation_libelle' => $vidange->libelle,
            'date_echeance' => now()->toDateString(),
            'rappel_jours' => 7,
            'statut' => 'planifiee',
            'user_id' => User::factory()->create()->id,
        ], $overrides));
    }

    public function test_notifie_les_utilisateurs_ayant_la_permission_operations_voir(): void
    {
        $operation = $this->creerOperation();
        $admin = User::factory()->create()->assignRole('admin');
        $gestionnaire = User::factory()->create()->assignRole('gestionnaire');

        $nombre = app(NotificationOperationProgrammeeService::class)->notifier();

        $this->assertSame(1, $nombre);
        $this->assertCount(1, $admin->notifications);
        $this->assertCount(0, $gestionnaire->notifications);
        $this->assertSame($operation->id, $admin->notifications->first()->data['operation_id']);
    }

    public function test_ignore_les_operations_sans_alerte_active(): void
    {
        $this->creerOperation(['date_echeance' => now()->addDays(60)->toDateString(), 'rappel_jours' => 7]);
        User::factory()->create()->assignRole('admin');

        $nombre = app(NotificationOperationProgrammeeService::class)->notifier();

        $this->assertSame(0, $nombre);
    }

    public function test_ignore_les_operations_deja_realisees(): void
    {
        $this->creerOperation(['statut' => 'realisee', 'date_realisation' => now()->toDateString()]);
        User::factory()->create()->assignRole('admin');

        $nombre = app(NotificationOperationProgrammeeService::class)->notifier();

        $this->assertSame(0, $nombre);
    }

    public function test_ne_notifie_pas_deux_fois_pour_le_meme_niveau_dalerte(): void
    {
        $this->creerOperation();
        $admin = User::factory()->create()->assignRole('admin');

        $service = app(NotificationOperationProgrammeeService::class);
        $premierAppel = $service->notifier();
        $secondAppel = $service->notifier();

        $this->assertSame(1, $premierAppel);
        $this->assertSame(0, $secondAppel);
        $this->assertCount(1, $admin->notifications);
    }

    public function test_renotifie_quand_le_niveau_dalerte_evolue(): void
    {
        $this->creerOperation(['date_echeance' => now()->toDateString(), 'rappel_jours' => 7]);
        $admin = User::factory()->create()->assignRole('admin');

        $service = app(NotificationOperationProgrammeeService::class);
        $service->notifier();

        $this->travelTo(now()->addDay());
        $nombre = $service->notifier();

        $this->assertSame(1, $nombre);
        $this->assertCount(2, $admin->notifications);
        $statuts = $admin->notifications->pluck('data.statut_alerte')->all();
        $this->assertContains('jour_j', $statuts);
        $this->assertContains('depasse', $statuts);
    }

    public function test_le_contenu_de_la_notification_reprend_les_snapshots_de_loperation(): void
    {
        $operation = $this->creerOperation();
        $admin = User::factory()->create()->assignRole('admin');

        app(NotificationOperationProgrammeeService::class)->notifier();

        $notification = $admin->notifications->first();
        $this->assertSame(OperationProgrammeeRappelNotification::class, $notification->type);
        $this->assertSame($operation->vehicule_code, $notification->data['vehicule_code']);
        $this->assertSame($operation->type_operation_libelle, $notification->data['type_operation_libelle']);
    }
}
