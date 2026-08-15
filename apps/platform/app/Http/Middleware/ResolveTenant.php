<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenancy\TenantContext;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function handle(Request $request, Closure $next, bool $required = true): Response
    {
        $companyIdentifier = $request->header('X-Company-Id')
            ?? $request->route('company')?->getKey()
            ?? $request->route('company');

        if ($companyIdentifier === null) {
            abort_unless(! $required, 422, 'A company scope is required.');

            return $next($request);
        }

        $company = Company::query()
            ->where('id', $companyIdentifier)
            ->orWhere('uuid', $companyIdentifier)
            ->orWhere('slug', $companyIdentifier)
            ->firstOrFail();

        $user = $request->user();
        setPermissionsTeamId(0);
        $isPlatformOperator = $user?->hasAnyRole(['platform-administrator', 'platform-support']) ?? false;

        abort_unless($isPlatformOperator || $user?->belongsToActiveCompany($company), 403, 'You do not have access to this company.');

        app(TenantContext::class)->establish(
            company: $company,
            actingUserId: $user?->getKey(),
            impersonatorId: $request->attributes->get('impersonator_id'),
        );

        setPermissionsTeamId($company->getKey());

        try {
            return $next($request);
        } finally {
            app(TenantContext::class)->clear();
            setPermissionsTeamId(0);
        }
    }
}
