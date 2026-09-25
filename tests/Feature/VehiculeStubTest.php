<?php

namespace Tests\Feature;

use App\Models\Vehicule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiculeStubTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicule_code_must_be_unique(): void
    {
        Vehicule::factory()->create(['code' => 'AL-001']);

        $this->expectException(QueryException::class);

        Vehicule::factory()->create(['code' => 'AL-001']);
    }

    public function test_soft_deleted_vehicule_keeps_its_row_for_history(): void
    {
        $vehicule = Vehicule::factory()->create();

        $vehicule->delete();

        $this->assertSoftDeleted($vehicule);
        $this->assertDatabaseHas('vehicules', ['id' => $vehicule->id]);
    }
}
