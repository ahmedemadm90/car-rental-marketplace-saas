<?php

declare(strict_types=1);

namespace App\Domain\Reservations\Services;

use App\Domain\Audit\AuditLogger;
use App\Domain\Reservations\ReservationStatus;
use App\Models\Reservation;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ReservationLifecycleService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function transition(Reservation $reservation, ReservationStatus $target, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $target, $reason): Reservation {
            $locked = Reservation::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($reservation->getKey());
            $current = $locked->status;

            if (! $current->canTransitionTo($target)) {
                throw new DomainException(sprintf('Transition from %s to %s is not permitted.', $current->value, $target->value));
            }

            $before = $locked->getAttributes();
            $attributes = ['status' => $target];

            match ($target) {
                ReservationStatus::Confirmed => $attributes['confirmed_at'] = now(),
                ReservationStatus::Cancelled => $attributes['cancelled_at'] = now(),
                ReservationStatus::CheckedOut => $attributes['checked_out_at'] = now(),
                ReservationStatus::Returned => $attributes['returned_at'] = now(),
                default => null,
            };

            if (in_array($target, [ReservationStatus::Expired, ReservationStatus::Cancelled, ReservationStatus::NoShow], true)) {
                $this->releaseAllocations($locked);
            }

            $locked->update($attributes);
            $locked->refresh();

            $this->audit->record('reservation.transitioned', $locked, $before, $locked->getAttributes(), [
                'from' => $current->value,
                'to' => $target->value,
                'reason' => $reason,
            ]);

            return $locked;
        }, 3);
    }

    public function cancellationFee(Reservation $reservation): int
    {
        $policy = $reservation->cancellation_policy_snapshot ?? [];
        $basisPoints = (int) ($policy['fee_basis_points'] ?? 0);
        $hoursBeforePickup = now()->diffInHours($reservation->pickup_at, false);
        $threshold = (int) ($policy['free_cancellation_before_hours'] ?? 0);

        if ($hoursBeforePickup >= $threshold) {
            return 0;
        }

        return (int) round($reservation->total_minor * $basisPoints / 10000, 0, PHP_ROUND_HALF_UP);
    }

    private function releaseAllocations(Reservation $reservation): void
    {
        $reservation->allocations()
            ->whereNull('released_at')
            ->whereIn('status', ['held', 'confirmed'])
            ->update(['status' => 'released', 'released_at' => now()]);
    }
}
