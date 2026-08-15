<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_id' => ['required', 'ulid'],
            'platform' => ['required', 'in:android,ios'],
            'app_version' => ['required', 'string', 'max:64'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ];
    }
}
