<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Models\Company;
use Illuminate\Contracts\Container\Container;
use LogicException;

final class TenantContext
{
    private ?Company $company = null;

    private ?int $actingUserId = null;

    private ?int $impersonatorId = null;

    public function __construct(private readonly Container $container) {}

    public function establish(Company $company, ?int $actingUserId = null, ?int $impersonatorId = null): void
    {
        if (! $company->isActive()) {
            throw new LogicException('An inactive company cannot become the active tenant.');
        }

        $this->company = $company;
        $this->actingUserId = $actingUserId;
        $this->impersonatorId = $impersonatorId;
    }

    public function clear(): void
    {
        $this->company = null;
        $this->actingUserId = null;
        $this->impersonatorId = null;
    }

    public function isEstablished(): bool
    {
        return $this->company !== null;
    }

    public function company(): Company
    {
        return $this->company ?? throw new LogicException('Tenant context has not been established.');
    }

    public function companyId(): int
    {
        return $this->company()->getKey();
    }

    public function actingUserId(): ?int
    {
        return $this->actingUserId;
    }

    public function impersonatorId(): ?int
    {
        return $this->impersonatorId;
    }
}
