<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/users/create', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'isAdmin' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_admin' => true,
            'password_changed' => true,
        ]);
    }
}
