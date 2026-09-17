<?php

namespace Tests\Feature\Stock;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_manage_articles_but_chef_mecanicien_cannot(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $article = Article::factory()->create();

        $this->assertTrue($gestionnaireStock->can('create', Article::class));
        $this->assertTrue($gestionnaireStock->can('update', $article));
        $this->assertFalse($chefMecanicien->can('create', Article::class));
        $this->assertFalse($chefMecanicien->can('update', $article));
    }

    public function test_only_gestionnaire_stock_and_admin_can_do_sortie_interne_and_vente(): void
    {
        $gestionnaireStock = User::factory()->create()->assignRole('gestionnaire_stock');
        $chefMecanicien = User::factory()->create()->assignRole('chef_mecanicien');

        $this->assertTrue($gestionnaireStock->can('sortieInterne', \App\Models\MouvementStock::class));
        $this->assertTrue($gestionnaireStock->can('sortieVente', \App\Models\MouvementStock::class));
        $this->assertFalse($chefMecanicien->can('sortieInterne', \App\Models\MouvementStock::class));
        $this->assertFalse($chefMecanicien->can('sortieVente', \App\Models\MouvementStock::class));
    }
}
