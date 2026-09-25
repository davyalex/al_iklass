<?php

namespace Tests\Feature;

use App\Models\OperationProgrammee;
use App\Models\TypeOperation;
use App\Models\User;
use App\Models\Vehicule;
use App\Notifications\OperationProgrammeeRappelNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeOperationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TypeOperationSeeder::class);
    }

    private function notifierUnUtilisateur(User $utilisateur): void
    {
        $vehicule = Vehicule::factory()->create();
        $vidange = TypeOperation::where('code', 'vidange')->firstOrFail();

        $operation = OperationProgrammee::create([
            'vehicule_id' => $vehicule->id,
            'vehicule_code' => $vehicule->code,
            'type_operation_id' => $vidange->id,
            'type_operation_code' => $vidange->code,
            'type_operation_libelle' => $vidange->libelle,
            'date_echeance' => now()->toDateString(),
            'rappel_jours' => 7,
            'statut' => 'planifiee',
            'user_id' => $utilisateur->id,
        ]);

        $utilisateur->notify(new OperationProgrammeeRappelNotification($operation, 'jour_j'));
    }

    public function test_index_retourne_le_compteur_et_la_liste(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->notifierUnUtilisateur($admin);

        $response = $this->actingAs($admin)->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonPath('non_lues', 1)
            ->assertJsonCount(1, 'notifications');
    }

    public function test_marquer_lue_marque_uniquement_la_notification_ciblee(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->notifierUnUtilisateur($admin);
        $this->notifierUnUtilisateur($admin);
        $notification = $admin->notifications->first();

        $response = $this->actingAs($admin)->postJson(route('notifications.lue', $notification->id));

        $response->assertOk()->assertJsonPath('non_lues', 1);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_marquer_tout_lu_marque_toutes_les_notifications(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $this->notifierUnUtilisateur($admin);
        $this->notifierUnUtilisateur($admin);

        $response = $this->actingAs($admin)->postJson(route('notifications.tout-lu'));

        $response->assertOk()->assertJsonPath('non_lues', 0);
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_un_utilisateur_ne_voit_pas_les_notifications_dun_autre(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $autreAdmin = User::factory()->create()->assignRole('admin');
        $this->notifierUnUtilisateur($autreAdmin);

        $response = $this->actingAs($admin)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('non_lues', 0)->assertJsonCount(0, 'notifications');
    }

    public function test_un_utilisateur_ne_peut_pas_marquer_lue_la_notification_dun_autre(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $autreAdmin = User::factory()->create()->assignRole('admin');
        $this->notifierUnUtilisateur($autreAdmin);
        $notification = $autreAdmin->notifications->first();

        $this->actingAs($admin)->postJson(route('notifications.lue', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
