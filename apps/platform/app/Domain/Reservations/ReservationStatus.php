<?php

declare(strict_types=1);

namespace App\Domain\Reservations;

enum ReservationStatus: string
{
    case Draft = 'draft';
    case Quoted = 'quoted';
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case CheckedOut = 'checked_out';
    case Extended = 'extended';
    case Returned = 'returned';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Expired = 'expired';
    case NoShow = 'no_show';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Quoted],
            self::Quoted => [self::PendingPayment, self::Expired, self::Cancelled],
            self::PendingPayment => [self::Confirmed, self::Expired, self::Cancelled],
            self::Confirmed => [self::CheckedOut, self::Cancelled, self::NoShow],
            self::CheckedOut => [self::Extended, self::Returned],
            self::Extended => [self::Returned],
            self::Returned => [self::Closed],
            self::Cancelled => [self::Refunded],
            self::Closed, self::Refunded, self::Expired, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
