<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Vehicle extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'vehicle_group_id', 'branch_id', 'uuid', 'registration_number', 'vin', 'model_year',
        'odometer_km', 'status', 'color', 'last_inspected_at',
    ];

    protected function casts(): array
    {
        return ['last_inspected_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::creating(function (self $vehicle): void {
            $vehicle->uuid ??= (string) Str::ulid();
        });
    }

    public function vehicleGroup(): BelongsTo
    {
        return $this->belongsTo(VehicleGroup::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
