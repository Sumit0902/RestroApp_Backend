<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/companies');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }

    public function test_store()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $companyData = Company::factory()->make()->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/add', $companyData);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }

    public function test_show()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;
        $company = Company::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/companies/' . $company->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }

    public function test_update()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;
        $company = Company::factory()->create();
        $updateData = ['company_name' => 'Updated Company Name'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/companies/' . $company->id . '/update', $updateData);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data', 'error']);
    }
}
