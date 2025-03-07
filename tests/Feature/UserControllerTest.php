<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $userData = User::factory()->make()->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/employee/add', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }

    public function test_show()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/employee/' . $user->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }
}
