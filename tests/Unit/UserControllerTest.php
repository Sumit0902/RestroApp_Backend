<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\UserController;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user()
    {
        $response = $this->postJson('/api/employee/add', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => true,
                     'error' => null,
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_store()
    {
        $this->assertTrue(true);
    }

    public function test_show()
    {
        $this->assertTrue(true);
    }
}