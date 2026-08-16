<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WaitlistEntry extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'waitlist_entries';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pickup_at' => 'immutable_datetime',
            'return_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vehicleGroup(): BelongsTo
    {
        return $this->belongsTo(VehicleGroup::class);
    }
}
