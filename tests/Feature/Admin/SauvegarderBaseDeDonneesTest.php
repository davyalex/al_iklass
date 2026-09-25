<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\Admin\SauvegarderBaseDeDonnees;
use App\Services\Admin\SauvegardeService;
use Database\Seeders\ParametreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SauvegarderBaseDeDonneesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametreSeeder::class);
    }

    public function test_ne_sauvegarde_pas_hors_de_lheure_configuree(): void
    {
        $this->travelTo(now()->setTime(14, 0));

        $this->mock(SauvegardeService::class, function ($mock) {
            $mock->shouldNotReceive('creer');
        });

        $this->artisan(SauvegarderBaseDeDonnees::class)->assertSuccessful();
    }

    public function test_sauvegarde_a_lheure_configuree(): void
    {
        $this->travelTo(now()->setTime(2, 0));

        $this->mock(SauvegardeService::class, function ($mock) {
            $mock->shouldReceive('creer')->once()->andReturn('al-iklass_test.sql.gz');
            $mock->shouldReceive('purger')->once()->andReturn(0);
        });

        $this->artisan(SauvegarderBaseDeDonnees::class)->assertSuccessful();
    }

    public function test_loption_force_ignore_lheure_configuree(): void
    {
        $this->travelTo(now()->setTime(14, 0));

        $this->mock(SauvegardeService::class, function ($mock) {
            $mock->shouldReceive('creer')->once()->andReturn('al-iklass_test.sql.gz');
            $mock->shouldReceive('purger')->once()->andReturn(0);
        });

        $this->artisan(SauvegarderBaseDeDonnees::class, ['--force' => true])->assertSuccessful();
    }
}
