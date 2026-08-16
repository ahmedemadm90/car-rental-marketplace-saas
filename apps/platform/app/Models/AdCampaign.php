<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AdCampaign extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'ad_campaigns';

    protected $guarded = [];
}
