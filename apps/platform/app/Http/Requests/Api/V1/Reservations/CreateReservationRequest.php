<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservations;

use Illuminate\Foundation\Http\FormRequest;

final class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'vehicle_group_id' => ['required', 'integer', 'exists:vehicle_groups,id'],
            'rate_plan_id' => ['required', 'integer', 'exists:rate_plans,id'],
            'pickup_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'return_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'pickup_at' => ['required', 'date', 'after:now'],
            'return_at' => ['required', 'date', 'after:pickup_at'],
            'promotion_code' => ['nullable', 'string', 'max:64'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
