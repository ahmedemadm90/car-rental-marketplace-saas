<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

final class SearchVehiclesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'pickup_at' => ['required', 'date', 'after:now'],
            'return_at' => ['required', 'date', 'after:pickup_at'],
            'pickup_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'return_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'category' => ['nullable', 'string', 'max:64'],
            'transmission' => ['nullable', 'in:automatic,manual'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,hybrid,electric'],
            'min_seats' => ['nullable', 'integer', 'min:1', 'max:15'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'sort' => ['nullable', 'in:price_asc,price_desc,capacity_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
