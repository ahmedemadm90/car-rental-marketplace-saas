<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Payment extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'reservation_id', 'customer_id', 'uuid', 'provider', 'provider_reference', 'type', 'status',
        'currency', 'amount_minor', 'idempotency_key', 'provider_payload', 'captured_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'captured_at' => 'immutable_datetime',
            'refunded_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $payment): void {
            $payment->uuid ??= (string) Str::ulid();
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
