<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class VehicleGroup extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'uuid', 'name', 'code', 'category', 'make', 'model', 'seats', 'doors', 'transmission',
        'fuel_type', 'air_conditioning', 'luggage_capacity', 'features', 'media', 'description', 'is_public', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'media' => 'array',
            'air_conditioning' => 'boolean',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $vehicleGroup): void {
            $vehicleGroup->uuid ??= (string) Str::ulid();
        });
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function ratePlans(): BelongsToMany
    {
        return $this->belongsToMany(RatePlan::class, 'vehicle_group_rate_plan');
    }

    public function activeVehicleCountAt(\DateTimeInterface $startsAt, \DateTimeInterface $endsAt): int
    {
        return $this->vehicles()
            ->where('status', 'available')
            ->whereDoesntHave('maintenanceRecords', function ($query) use ($startsAt, $endsAt): void {
                $query->whereIn('status', ['scheduled', 'in_progress'])
                    ->where('starts_at', '<', $endsAt)
                    ->where(function ($blockedQuery) use ($startsAt): void {
                        $blockedQuery->whereNull('ends_at')->orWhere('ends_at', '>', $startsAt);
                    });
            })
            ->count();
    }
}
