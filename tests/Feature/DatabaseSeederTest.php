<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\MultiYearDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `php artisan db:seed` is what a fresh Docker install runs on an empty database.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_an_empty_database_creates_the_demo_account_with_all_demo_data()
    {
        $this->artisan('db:seed')->assertSuccessful();

        $demo = User::where('email', DemoDataSeeder::EMAIL)->firstOrFail();

        $this->assertSame(1, User::count());
        $this->assertSame(4, $demo->workspaces()->count());
        $this->assertTrue($demo->workspaces()->where('name', MultiYearDemoSeeder::WORKSPACE)->exists());
    }
}
