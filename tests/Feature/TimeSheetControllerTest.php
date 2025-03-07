<?php

namespace Tests\Feature;

use App\Models\TimeSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSheetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/1/timesheets/check-in', [
                             'employee_id' => $user->id,
                             'company_id' => 1,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'message', 'timesheet']);
    }

    public function test_check_out()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;
        $timesheet = TimeSheet::factory()->create(['user_id' => $user->id, 'company_id' => 1]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/1/timesheets/check-out', [
                             'employee_id' => $user->id,
                             'company_id' => 1,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'timesheet']);
    }
}
