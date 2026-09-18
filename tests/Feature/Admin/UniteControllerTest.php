<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Unite;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gestionnaire_stock_can_view_unites_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $this->actingAs($user)->get(route('admin.unites.index'))
            ->assertOk()
            ->assertViewIs('admin.unites.index');
    }

    public function test_gestionnaire_without_permission_cannot_view_unites_index(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire');

        $this->actingAs($user)->get(route('admin.unites.index'))->assertForbidden();
    }

    public function test_gestionnaire_stock_can_create_unite(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');

        $response = $this->actingAs($user)->postJson(route('admin.unites.store'), [
            'libelle' => 'pièce',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('unites', ['libelle' => 'pièce']);
    }

    public function test_chef_mecanicien_cannot_create_unite(): void
    {
        $user = User::factory()->create()->assignRole('chef_mecanicien');

        $this->actingAs($user)->postJson(route('admin.unites.store'), [
            'libelle' => 'pièce',
        ])->assertForbidden();
    }

    public function test_store_rejects_duplicate_libelle(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        Unite::create(['libelle' => 'pièce', 'actif' => true]);

        $this->actingAs($user)->postJson(route('admin.unites.store'), [
            'libelle' => 'pièce',
        ])->assertUnprocessable();
    }

    public function test_gestionnaire_stock_can_update_unite(): void
    {
        $user = User::factory()->create()->assignRole('gestionnaire_stock');
        $unite = Unite::create(['libelle' => 'pièce', 'actif' => true]);

        $this->actingAs($user)->putJson(route('admin.unites.update', $unite), [
            'libelle' => 'unité',
        ])->assertOk();

        $this->assertDatabaseHas('unites', ['id' => $unite->id, 'libelle' => 'unité']);
    }

    public function test_destroy_soft_deletes_unite_and_keeps_article_link(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $unite = Unite::create(['libelle' => 'pièce', 'actif' => true]);
        $article = Article::factory()->create(['unite_id' => $unite->id]);

        $this->actingAs($user)->deleteJson(route('admin.unites.destroy', $unite))
            ->assertOk();

        $this->assertSoftDeleted($unite);
        $this->assertSame($unite->id, $article->fresh()->unite_id);
    }
}
