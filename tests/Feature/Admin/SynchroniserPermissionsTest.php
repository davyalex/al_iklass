<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\Admin\SynchroniserPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SynchroniserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_commande_cree_les_permissions_manquantes(): void
    {
        $this->assertSame(0, Permission::count());

        $this->artisan(SynchroniserPermissions::class)->assertSuccessful();

        $this->assertGreaterThan(0, Permission::count());
    }
}
