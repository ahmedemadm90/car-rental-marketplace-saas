<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\User;
use App\Models\VehicleGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

final class CustomerReservationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_reschedule_and_sign_reservation(): void
    {
        $company = Company::query()->create(['uuid' => (string) Str::uuid(), 'slug' => 'apex', 'display_name' => 'Apex Rentals', 'legal_name' => 'Apex Rentals LLC', 'status' => 'active', 'currency' => 'USD']);
        $branch = Branch::query()->create(['company_id' => $company->getKey(), 'code' => 'MAIN-DXB', 'name' => 'Main', 'city' => 'Dubai', 'country_code' => 'AE', 'timezone' => 'Asia/Dubai', 'address_line_1' => 'Downtown', 'is_active' => true]);
        $group = VehicleGroup::query()->create(['company_id' => $company->getKey(), 'code' => 'SUV-PREM', 'name' => 'SUV', 'category' => 'SUV', 'transmission' => 'automatic', 'fuel_type' => 'petrol', 'seats' => 5, 'is_public' => true, 'is_active' => true]);
        $ratePlan = RatePlan::query()->create(['company_id' => $company->getKey(), 'code' => 'STD', 'name' => 'Standard', 'daily_rate_minor' => 10000, 'deposit_minor' => 25000, 'currency' => 'USD', 'is_active' => true]);
        $ratePlan->vehicleGroups()->attach($group->getKey());

        $customer = User::query()->create(['name' => 'Customer Test', 'email' => 'customer@test.com', 'password' => bcrypt('password123'), 'status' => 'active']);
        $token = JWTAuth::fromUser($customer);

        $reservation = Reservation::query()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'vehicle_group_id' => $group->getKey(),
            'pickup_branch_id' => $branch->getKey(),
            'return_branch_id' => $branch->getKey(),
            'rate_plan_id' => $ratePlan->getKey(),
            'status' => 'pending_payment',
            'pickup_at' => now()->addDay(),
            'return_at' => now()->addDays(3),
            'currency' => 'USD',
            'subtotal_minor' => 20000,
            'tax_minor' => 1000,
            'deposit_minor' => 25000,
            'total_minor' => 21000,
            'pricing_snapshot' => [],
            'cancellation_policy_snapshot' => [],
            'customer_snapshot' => [],
            'uuid' => (string) Str::uuid(),
            'reference' => 'VR-TEST01',
        ]);

        $this->withToken($token)
            ->postJson("/api/v1/reservations/{$reservation->getKey()}/reschedule", [
                'pickup_at' => now()->addDays(2)->toIso8601String(),
                'return_at' => now()->addDays(5)->toIso8601String(),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->withoutExceptionHandling();

        $this->withToken($token)
            ->postJson("/api/v1/reservations/{$reservation->getKey()}/sign", [
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->withToken($token)
            ->postJson("/api/v1/reservations/{$reservation->getKey()}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }
}
