<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Domain\Tenancy\TenantContext;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** @mixin Model */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $context = app(TenantContext::class);

            if ($context->isEstablished()) {
                $builder->where($builder->getModel()->qualifyColumn('company_id'), $context->companyId());
            }
        });

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if ($model->getAttribute('company_id') === null && $context->isEstablished()) {
                $model->setAttribute('company_id', $context->companyId());
            }

            if ($model->getAttribute('company_id') === null) {
                throw new LogicException(sprintf('%s requires a company_id.', $model::class));
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;

        return $query->withoutGlobalScope('company')->where($query->getModel()->qualifyColumn('company_id'), $companyId);
    }
}
