<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletEntry;
use DomainException;
use Illuminate\Support\Facades\DB;

final class WalletLedgerService
{
    /** @param array<string, mixed> $metadata */
    public function post(User $user, string $currency, int $amountMinor, string $type, string $idempotencyKey, ?string $referenceType = null, ?int $referenceId = null, array $metadata = []): WalletEntry
    {
        if ($amountMinor === 0) {
            throw new DomainException('A wallet entry amount cannot be zero.');
        }

        return DB::transaction(function () use ($user, $currency, $amountMinor, $type, $idempotencyKey, $referenceType, $referenceId, $metadata): WalletEntry {
            $existing = WalletEntry::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }

            Wallet::query()->firstOrCreate(['user_id' => $user->getKey(), 'currency' => $currency]);
            $wallet = Wallet::query()
                ->where('user_id', $user->getKey())
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $nextBalance = (int) $wallet->balance_minor + $amountMinor;
            if ($nextBalance < 0) {
                throw new DomainException('Insufficient wallet balance.');
            }

            $entry = $wallet->entries()->create([
                'type' => $type,
                'amount_minor' => $amountMinor,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
                'occurred_at' => now(),
            ]);

            $wallet->update(['balance_minor' => $nextBalance]);

            return $entry;
        }, 3);
    }
}
