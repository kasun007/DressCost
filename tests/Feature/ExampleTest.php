<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_opens_the_login_for_guests()
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_the_home_page_opens_the_dashboard_when_logged_in()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }
}
