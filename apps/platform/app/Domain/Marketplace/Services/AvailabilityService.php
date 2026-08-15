<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Services;

use App\Models\ReservationAllocation;
use App\Models\VehicleGroup;
use Carbon\CarbonImmutable;

final class AvailabilityService
{
    public function availableUnits(VehicleGroup $vehicleGroup, CarbonImmutable $pickupAt, CarbonImmutable $returnAt): int
    {
        $capacity = $vehicleGroup->activeVehicleCountAt($pickupAt, $returnAt);

        $allocated = ReservationAllocation::query()
            ->forCompany($vehicleGroup->company_id)
            ->where('vehicle_group_id', $vehicleGroup->getKey())
            ->whereIn('status', ['held', 'confirmed', 'checked_out'])
            ->whereNull('released_at')
            ->where('starts_at', '<', $returnAt)
            ->where('ends_at', '>', $pickupAt)
            ->count();

        return max(0, $capacity - $allocated);
    }

    public function isAvailable(VehicleGroup $vehicleGroup, CarbonImmutable $pickupAt, CarbonImmutable $returnAt): bool
    {
        return $this->availableUnits($vehicleGroup, $pickupAt, $returnAt) > 0;
    }
}
