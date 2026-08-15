<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ApiAuthenticationAndTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mobile_user_can_sign_in_and_register_a_device(): void
    {
        $user = User::factory()->create([
            'email' => 'renter@example.test',
            'password' => Hash::make('CorrectPassword!123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/mobile/login', [
            'email' => $user->email,
            'password' => 'CorrectPassword!123',
            'device_id' => '01JQ3GDE6Y7E1WJ1JTWQF02GZX',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'push-token-001',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->getKey(),
            'device_id' => '01JQ3GDE6Y7E1WJ1JTWQF02GZX',
            'platform' => 'android',
        ]);
    }

    public function test_a_company_member_can_resolve_only_their_active_company_context(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $company = Company::query()->create([
            'legal_name' => 'Tenant One LLC',
            'display_name' => 'Tenant One',
            'slug' => 'tenant-one',
            'status' => Company::STATUS_ACTIVE,
        ]);
        $otherCompany = Company::query()->create([
            'legal_name' => 'Tenant Two LLC',
            'display_name' => 'Tenant Two',
            'slug' => 'tenant-two',
            'status' => Company::STATUS_ACTIVE,
        ]);
        $company->users()->attach($member->getKey(), ['status' => 'active', 'joined_at' => now()]);

        $token = auth('api')->login($member);

        $this->withToken($token)
            ->withHeader('X-Company-Id', (string) $company->getKey())
            ->getJson('/api/v1/company/context')
            ->assertOk()
            ->assertJsonPath('data.id', $company->getKey());

        $this->withToken($token)
            ->withHeader('X-Company-Id', (string) $otherCompany->getKey())
            ->getJson('/api/v1/company/context')
            ->assertForbidden();
    }
}
