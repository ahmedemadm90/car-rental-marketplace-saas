<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Branch extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'uuid',
        'name',
        'code',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'region',
        'country_code',
        'postal_code',
        'latitude',
        'longitude',
        'timezone',
        'opening_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_hours' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $branch): void {
            $branch->uuid ??= (string) Str::ulid();
            $branch->is_active ??= true;
        });
    }
}
