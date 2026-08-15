<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Services;

use App\Models\RatePlan;
use Carbon\CarbonImmutable;
use DomainException;

final class PricingService
{
    /**
     * @return array{subtotal_minor:int,discount_minor:int,tax_minor:int,fee_minor:int,deposit_minor:int,total_minor:int,currency:string,days:int,breakdown:array<int,array<string,mixed>>}
     */
    public function quote(RatePlan $ratePlan, CarbonImmutable $pickupAt, CarbonImmutable $returnAt, ?string $promotionCode = null): array
    {
        if ($returnAt->lessThanOrEqualTo($pickupAt)) {
            throw new DomainException('Return time must be later than pickup time.');
        }

        if (! $ratePlan->isEffectiveAt($pickupAt)) {
            throw new DomainException('The selected rate plan is not effective for pickup time.');
        }

        $days = max(1, (int) ceil($pickupAt->diffInMinutes($returnAt) / 1440));
        $dailyRate = (int) $ratePlan->daily_rate_minor;
        $subtotal = $dailyRate * $days;
        $discount = $this->promotionDiscount($ratePlan, $promotionCode, $subtotal);
        $fee = $this->sumFlatCharges($ratePlan->fees ?? [], $days);
        $taxable = max(0, $subtotal - $discount + $fee);
        $tax = $this->sumTax($ratePlan->taxes ?? [], $taxable);
        $deposit = (int) $ratePlan->deposit_minor;

        return [
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discount,
            'tax_minor' => $tax,
            'fee_minor' => $fee,
            'deposit_minor' => $deposit,
            'total_minor' => $taxable + $tax,
            'currency' => $ratePlan->currency,
            'days' => $days,
            'breakdown' => [
                ['code' => 'daily_rate', 'quantity' => $days, 'unit_minor' => $dailyRate, 'total_minor' => $subtotal],
                ['code' => 'promotion', 'quantity' => 1, 'unit_minor' => -$discount, 'total_minor' => -$discount],
                ['code' => 'fees', 'quantity' => 1, 'unit_minor' => $fee, 'total_minor' => $fee],
                ['code' => 'taxes', 'quantity' => 1, 'unit_minor' => $tax, 'total_minor' => $tax],
                ['code' => 'security_deposit', 'quantity' => 1, 'unit_minor' => $deposit, 'total_minor' => $deposit],
            ],
        ];
    }

    /** @param array<int, array<string, mixed>> $fees */
    private function sumFlatCharges(array $fees, int $days): int
    {
        return array_reduce($fees, static function (int $total, array $fee) use ($days): int {
            if (($fee['enabled'] ?? true) !== true) {
                return $total;
            }

            $amount = (int) ($fee['amount_minor'] ?? 0);
            $multiplier = ($fee['per'] ?? 'rental') === 'day' ? $days : 1;

            return $total + ($amount * $multiplier);
        }, 0);
    }

    /** @param array<int, array<string, mixed>> $taxes */
    private function sumTax(array $taxes, int $taxable): int
    {
        return array_reduce($taxes, static function (int $total, array $tax) use ($taxable): int {
            if (($tax['enabled'] ?? true) !== true) {
                return $total;
            }

            $basisPoints = (int) ($tax['rate_basis_points'] ?? 0);

            return $total + (int) round($taxable * $basisPoints / 10000, 0, PHP_ROUND_HALF_UP);
        }, 0);
    }

    private function promotionDiscount(RatePlan $ratePlan, ?string $promotionCode, int $subtotal): int
    {
        if ($promotionCode === null || $promotionCode === '') {
            return 0;
        }

        $promotion = collect($ratePlan->rules ?? [])
            ->first(fn (array $rule): bool => ($rule['type'] ?? null) === 'promotion' && hash_equals((string) ($rule['code'] ?? ''), $promotionCode));

        if ($promotion === null) {
            return 0;
        }

        $discount = match ($promotion['mode'] ?? 'fixed') {
            'percent' => (int) round($subtotal * ((int) ($promotion['value_basis_points'] ?? 0)) / 10000, 0, PHP_ROUND_HALF_UP),
            default => (int) ($promotion['value_minor'] ?? 0),
        };

        return min($subtotal, max(0, $discount));
    }
}
