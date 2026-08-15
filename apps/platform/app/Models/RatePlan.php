<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

final class RatePlan extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'uuid', 'name', 'code', 'currency', 'daily_rate_minor', 'deposit_minor',
        'included_km_per_day', 'extra_km_rate_minor', 'rules', 'fees', 'taxes', 'active_from', 'active_until', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'fees' => 'array',
            'taxes' => 'array',
            'active_from' => 'immutable_datetime',
            'active_until' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $ratePlan): void {
            $ratePlan->uuid ??= (string) Str::ulid();
        });
    }

    public function vehicleGroups(): BelongsToMany
    {
        return $this->belongsToMany(VehicleGroup::class, 'vehicle_group_rate_plan');
    }

    public function isEffectiveAt(\DateTimeInterface $time): bool
    {
        return $this->is_active
            && ($this->active_from === null || $this->active_from <= $time)
            && ($this->active_until === null || $this->active_until > $time);
    }
}
