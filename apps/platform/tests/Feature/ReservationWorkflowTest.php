<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\RatePlan;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReservationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_a_time_limited_reservation_hold_and_replay_idempotently(): void
    {
        $company = Company::query()->create([
            'legal_name' => 'Drive Well LLC',
            'display_name' => 'Drive Well',
            'slug' => 'drive-well',
            'status' => Company::STATUS_ACTIVE,
            'currency' => 'USD',
        ]);
        $branch = Branch::query()->withoutGlobalScopes()->create([
            'company_id' => $company->getKey(),
            'name' => 'Central',
            'code' => 'CTR',
            'address_line_1' => '1 Central Street',
            'city' => 'Dubai',
            'country_code' => 'AE',
            'timezone' => 'Asia/Dubai',
        ]);
        $group = VehicleGroup::query()->withoutGlobalScopes()->create([
            'company_id' => $company->getKey(),
            'name' => 'Premium SUV',
            'code' => 'SUV-P',
            'category' => 'SUV',
            'seats' => 5,
            'transmission' => 'automatic',
            'fuel_type' => 'hybrid',
        ]);
        Vehicle::query()->withoutGlobalScopes()->create([
            'company_id' => $company->getKey(),
            'vehicle_group_id' => $group->getKey(),
            'branch_id' => $branch->getKey(),
            'registration_number' => 'TEST-123',
            'status' => 'available',
        ]);
        $ratePlan = RatePlan::query()->withoutGlobalScopes()->create([
            'company_id' => $company->getKey(),
            'name' => 'Flexible',
            'code' => 'FLEX',
            'currency' => 'USD',
            'daily_rate_minor' => 10000,
            'deposit_minor' => 25000,
            'taxes' => [['rate_basis_points' => 500]],
        ]);
        $ratePlan->vehicleGroups()->attach($group->getKey());
        $customer = User::factory()->create(['status' => 'active']);
        $token = auth('api')->login($customer);
        $payload = [
            'vehicle_group_id' => $group->getKey(),
            'rate_plan_id' => $ratePlan->getKey(),
            'pickup_branch_id' => $branch->getKey(),
            'return_branch_id' => $branch->getKey(),
            'pickup_at' => now()->addDays(2)->startOfHour()->toIso8601String(),
            'return_at' => now()->addDays(4)->startOfHour()->toIso8601String(),
        ];

        $response = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'test-reservation-key-0001')
            ->postJson('/api/v1/reservations', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonPath('data.total_minor', 21000);

        $this->assertDatabaseHas('reservation_allocations', ['status' => 'held']);

        $replay = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'test-reservation-key-0001')
            ->postJson('/api/v1/reservations', $payload)
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame($response->json('data.reference'), $replay->json('data.reference'));
        $this->assertDatabaseCount('reservations', 1);
    }
}
