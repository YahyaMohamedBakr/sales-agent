<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?User $user = null;

    protected function authenticate(): User
    {
        $this->user ??= User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test-admin@example.com',
        ]);

        $this->actingAs($this->user);

        return $this->user;
    }
}
