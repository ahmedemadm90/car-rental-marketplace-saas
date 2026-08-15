<?php

declare(strict_types=1);

namespace App\Domain\Reservations\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Marketplace\Services\AvailabilityService;
use App\Domain\Pricing\Services\PricingService;
use App\Domain\Reservations\ReservationStatus;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationAllocation;
use App\Models\User;
use App\Models\VehicleGroup;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CreateReservationAction
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array{pickup_at:string,return_at:string,pickup_branch_id:int,return_branch_id:int,promotion_code?:string,customer_notes?:string} $input */
    public function execute(User $customer, VehicleGroup $vehicleGroup, RatePlan $ratePlan, array $input): Reservation
    {
        $pickupAt = CarbonImmutable::parse($input['pickup_at'])->utc();
        $returnAt = CarbonImmutable::parse($input['return_at'])->utc();

        if ($pickupAt->lessThanOrEqualTo(now()) || $returnAt->lessThanOrEqualTo($pickupAt)) {
            throw new DomainException('Reservation times must be a future, non-empty interval.');
        }

        return DB::transaction(function () use ($customer, $vehicleGroup, $ratePlan, $input, $pickupAt, $returnAt): Reservation {
            $lockedGroup = VehicleGroup::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($vehicleGroup->getKey());

            if (! $lockedGroup->is_active || ! $lockedGroup->is_public || $lockedGroup->company_id !== $ratePlan->company_id) {
                throw new DomainException('The selected vehicle group or rate plan is unavailable.');
            }

            if (! $this->availability->isAvailable($lockedGroup, $pickupAt, $returnAt)) {
                throw new DomainException('No vehicles remain available for the requested interval.');
            }

            $quote = $this->pricing->quote($ratePlan, $pickupAt, $returnAt, $input['promotion_code'] ?? null);
            $reservation = Reservation::query()->withoutGlobalScopes()->create([
                'company_id' => $lockedGroup->company_id,
                'customer_id' => $customer->getKey(),
                'vehicle_group_id' => $lockedGroup->getKey(),
                'pickup_branch_id' => $input['pickup_branch_id'],
                'return_branch_id' => $input['return_branch_id'],
                'rate_plan_id' => $ratePlan->getKey(),
                'status' => ReservationStatus::PendingPayment,
                'pickup_at' => $pickupAt,
                'return_at' => $returnAt,
                'hold_expires_at' => now()->addMinutes(15),
                'currency' => $quote['currency'],
                'subtotal_minor' => $quote['subtotal_minor'],
                'discount_minor' => $quote['discount_minor'],
                'tax_minor' => $quote['tax_minor'],
                'fee_minor' => $quote['fee_minor'],
                'deposit_minor' => $quote['deposit_minor'],
                'total_minor' => $quote['total_minor'],
                'pricing_snapshot' => $quote,
                'cancellation_policy_snapshot' => $this->cancellationPolicy($ratePlan),
                'customer_snapshot' => ['name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone],
                'customer_notes' => $input['customer_notes'] ?? null,
            ]);

            ReservationAllocation::query()->withoutGlobalScopes()->create([
                'company_id' => $lockedGroup->company_id,
                'reservation_id' => $reservation->getKey(),
                'vehicle_group_id' => $lockedGroup->getKey(),
                'starts_at' => $pickupAt,
                'ends_at' => $returnAt,
                'status' => 'held',
            ]);

            $this->audit->record('reservation.created', $reservation, null, $reservation->getAttributes(), ['source' => 'customer']);

            return $reservation->load(['vehicleGroup', 'pickupBranch', 'returnBranch']);
        }, 3);
    }

    /** @return array<string, mixed> */
    private function cancellationPolicy(RatePlan $ratePlan): array
    {
        return collect($ratePlan->rules ?? [])
            ->first(fn (array $rule): bool => ($rule['type'] ?? null) === 'cancellation') ?? ['type' => 'cancellation', 'fee_basis_points' => 0];
    }
}
