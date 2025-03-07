<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $notificationData = Notification::factory()->make()->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/1/notification/add', $notificationData);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }

    public function test_mark_as_read()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;
        $notification = Notification::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/1/notification/' . $notification->id . '/mark_read');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }
}
