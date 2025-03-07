<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $leaveData = Leave::factory()->make()->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/1/leave-management/myleaves/' . $user->id . '/request', $leaveData);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }

    public function test_approve()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;
        $leave = Leave::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson('/api/companies/1/leave-management/' . $leave->id . '/approve', [
                             'status' => 'approved',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }
}
