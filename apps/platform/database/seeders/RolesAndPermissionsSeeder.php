<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    private const COMPANY_ROLE_PERMISSIONS = [
        'company-owner' => ['company.settings.manage', 'fleet.manage', 'pricing.manage', 'reservations.manage', 'operations.manage', 'finance.manage', 'reports.view', 'audit.view'],
        'fleet-manager' => ['fleet.manage', 'pricing.manage', 'operations.manage', 'reports.view'],
        'branch-manager' => ['fleet.view', 'reservations.manage', 'operations.manage', 'reports.view'],
        'counter-agent' => ['fleet.view', 'reservations.manage', 'operations.execute'],
        'finance-officer' => ['finance.manage', 'reports.view', 'audit.view'],
    ];

    /** @var list<string> */
    private const COMPANY_PERMISSIONS = [
        'company.settings.manage', 'fleet.view', 'fleet.manage', 'pricing.manage', 'reservations.manage',
        'operations.manage', 'operations.execute', 'finance.manage', 'reports.view', 'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        setPermissionsTeamId(0);

        foreach (['platform.tenants.manage', 'platform.cms.manage', 'platform.support.manage', 'platform.audit.view', 'platform.monitoring.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $platformAdministrator = Role::findOrCreate('platform-administrator', 'web');
        $platformAdministrator->syncPermissions(Permission::query()->whereNull('company_id')->get());
        Role::findOrCreate('platform-support', 'web')->syncPermissions(['platform.support.manage', 'platform.audit.view']);

        Company::query()->each(function (Company $company): void {
            setPermissionsTeamId($company->getKey());

            foreach (self::COMPANY_PERMISSIONS as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            foreach (self::COMPANY_ROLE_PERMISSIONS as $role => $permissions) {
                Role::findOrCreate($role, 'web')->syncPermissions($permissions);
            }
        });

        setPermissionsTeamId(0);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
