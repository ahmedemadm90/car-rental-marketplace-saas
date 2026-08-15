<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toAtomString(),
            'companies' => $this->whenLoaded('companies', fn (): array => $this->companies
                ->map(fn ($company): array => [
                    'id' => $company->getKey(),
                    'uuid' => $company->uuid,
                    'name' => $company->display_name,
                    'slug' => $company->slug,
                    'membership_status' => $company->pivot->status,
                    'branch_id' => $company->pivot->branch_id,
                    'is_owner' => (bool) $company->pivot->is_owner,
                ])
                ->values()
                ->all()),
            'permissions' => $this->when($request->user()?->is($this->resource), fn (): array => $this->getAllPermissions()
                ->pluck('name')
                ->values()
                ->all()),
        ];
    }
}
