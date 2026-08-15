<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $platformAdmin = User::query()->firstOrCreate(
            ['email' => 'admin@voyagerrent.test'],
            [
                'name' => 'Platform Administrator',
                'password' => Hash::make('ChangeMe!12345'),
                'locale' => 'en',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $companies = collect([
            ['legal_name' => 'Atlas Mobility LLC', 'display_name' => 'Atlas Mobility', 'slug' => 'atlas-mobility', 'email' => 'ops@atlas.test', 'country_code' => 'AE', 'currency' => 'AED', 'timezone' => 'Asia/Dubai'],
            ['legal_name' => 'Nile Drive Co.', 'display_name' => 'Nile Drive', 'slug' => 'nile-drive', 'email' => 'ops@nile.test', 'country_code' => 'EG', 'currency' => 'EGP', 'timezone' => 'Africa/Cairo'],
        ])->map(function (array $attributes): Company {
            return Company::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                array_merge($attributes, ['uuid' => (string) Str::ulid(), 'status' => Company::STATUS_ACTIVE, 'locale' => 'en']),
            );
        });

        foreach ($companies as $company) {
            $branch = Branch::query()->withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->getKey(), 'code' => 'HQ'],
                [
                    'uuid' => (string) Str::ulid(),
                    'name' => $company->display_name.' Headquarters',
                    'address_line_1' => '100 Marketplace Avenue',
                    'city' => $company->country_code === 'AE' ? 'Dubai' : 'Cairo',
                    'country_code' => $company->country_code,
                    'timezone' => $company->timezone,
                    'opening_hours' => ['mon' => ['09:00', '18:00'], 'tue' => ['09:00', '18:00'], 'wed' => ['09:00', '18:00'], 'thu' => ['09:00', '18:00'], 'fri' => ['09:00', '18:00']],
                    'is_active' => true,
                ],
            );

            Subscription::query()->firstOrCreate(
                ['company_id' => $company->getKey(), 'plan_code' => 'enterprise'],
                [
                    'status' => 'active',
                    'seat_limit' => 250,
                    'vehicle_limit' => 5000,
                    'commission_rate_basis_points' => 750,
                    'current_period_starts_at' => now()->startOfMonth(),
                    'current_period_ends_at' => now()->addMonth()->startOfMonth(),
                ],
            );

            $owner = User::query()->firstOrCreate(
                ['email' => 'owner+'.$company->slug.'@voyagerrent.test'],
                [
                    'name' => $company->display_name.' Owner',
                    'password' => Hash::make('ChangeMe!12345'),
                    'locale' => 'en',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );

            $company->users()->syncWithoutDetaching([
                $owner->getKey() => ['branch_id' => $branch->getKey(), 'is_owner' => true, 'status' => 'active', 'joined_at' => now()],
            ]);
        }

        $customer = User::query()->firstOrCreate(
            ['email' => 'customer@voyagerrent.test'],
            [
                'name' => 'Sample Customer',
                'password' => Hash::make('ChangeMe!12345'),
                'locale' => 'en',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $this->call(RolesAndPermissionsSeeder::class);
        setPermissionsTeamId(0);
        $platformAdmin->assignRole('platform-administrator');

        foreach ($companies as $company) {
            setPermissionsTeamId($company->getKey());
            $owner = User::query()->where('email', 'owner+'.$company->slug.'@voyagerrent.test')->firstOrFail();
            $owner->assignRole('company-owner');
        }

        setPermissionsTeamId(0);
        $customer->syncRoles([]);
    }
}
