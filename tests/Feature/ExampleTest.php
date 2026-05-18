<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_redirects_to_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_api_requires_auth(): void
    {
        $response = $this->getJson('/api/analytics/overview');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->authenticate();

        $response = $this->get('/dashboard');

        $response->assertOk();
    }

    public function test_authenticated_user_can_access_api(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/analytics/overview');

        $response->assertOk();
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = \App\Models\User::factory()->create([
            'password' => bcrypt('secret'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
