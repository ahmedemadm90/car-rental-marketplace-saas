<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Reservations\ReservationStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Reservation extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'vehicle_group_id', 'pickup_branch_id', 'return_branch_id', 'rate_plan_id', 'uuid',
        'reference', 'status', 'pickup_at', 'return_at', 'hold_expires_at', 'currency', 'subtotal_minor', 'discount_minor',
        'tax_minor', 'fee_minor', 'deposit_minor', 'total_minor', 'cancellation_fee_minor', 'pricing_snapshot',
        'cancellation_policy_snapshot', 'customer_snapshot', 'customer_notes', 'confirmed_at', 'cancelled_at', 'checked_out_at', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'pickup_at' => 'immutable_datetime',
            'return_at' => 'immutable_datetime',
            'hold_expires_at' => 'immutable_datetime',
            'pricing_snapshot' => 'array',
            'cancellation_policy_snapshot' => 'array',
            'customer_snapshot' => 'array',
            'confirmed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'checked_out_at' => 'immutable_datetime',
            'returned_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $reservation): void {
            $reservation->uuid ??= (string) Str::ulid();
            $reservation->reference ??= 'VR-'.strtoupper(Str::random(10));
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vehicleGroup(): BelongsTo
    {
        return $this->belongsTo(VehicleGroup::class);
    }

    public function pickupBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'pickup_branch_id');
    }

    public function returnBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'return_branch_id');
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ReservationAllocation::class);
    }

    public function canBeChangedBy(User $user): bool
    {
        return $this->customer_id === $user->getKey() && in_array($this->status, [ReservationStatus::Quoted, ReservationStatus::PendingPayment, ReservationStatus::Confirmed], true);
    }
}
