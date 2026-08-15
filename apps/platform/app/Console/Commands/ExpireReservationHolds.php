<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Reservations\ReservationStatus;
use App\Domain\Reservations\Services\ReservationLifecycleService;
use App\Models\Reservation;
use Illuminate\Console\Command;

final class ExpireReservationHolds extends Command
{
    protected $signature = 'reservations:expire-holds {--chunk=100 : Number of holds to process at a time}';

    protected $description = 'Expire overdue pending-payment reservation holds and release their inventory allocations.';

    public function handle(ReservationLifecycleService $lifecycle): int
    {
        $expired = 0;

        Reservation::query()
            ->withoutGlobalScopes()
            ->where('status', ReservationStatus::PendingPayment)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($reservations) use ($lifecycle, &$expired): void {
                foreach ($reservations as $reservation) {
                    $lifecycle->transition($reservation, ReservationStatus::Expired, 'Payment hold expired.');
                    $expired++;
                }
            });

        $this->components->info("Expired {$expired} reservation hold(s).");

        return self::SUCCESS;
    }
}
