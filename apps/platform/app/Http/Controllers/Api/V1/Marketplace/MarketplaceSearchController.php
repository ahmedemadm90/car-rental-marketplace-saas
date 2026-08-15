<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Domain\Marketplace\Services\AvailabilityService;
use App\Domain\Pricing\Services\PricingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketplace\SearchVehiclesRequest;
use App\Models\RatePlan;
use App\Models\VehicleGroup;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class MarketplaceSearchController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    public function __invoke(SearchVehiclesRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $pickupAt = CarbonImmutable::parse($filters['pickup_at'])->utc();
        $returnAt = CarbonImmutable::parse($filters['return_at'])->utc();

        $groups = VehicleGroup::query()
            ->withoutGlobalScopes()
            ->with('company:id,uuid,display_name,slug,currency')
            ->where('is_public', true)
            ->where('is_active', true)
            ->when($filters['company_id'] ?? null, fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['transmission'] ?? null, fn ($query, string $transmission) => $query->where('transmission', $transmission))
            ->when($filters['fuel_type'] ?? null, fn ($query, string $fuelType) => $query->where('fuel_type', $fuelType))
            ->when($filters['min_seats'] ?? null, fn ($query, int $seats) => $query->where('seats', '>=', $seats))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 20));

        $offers = $groups->getCollection()
            ->map(function (VehicleGroup $group) use ($pickupAt, $returnAt): ?array {
                if (! $this->availability->isAvailable($group, $pickupAt, $returnAt)) {
                    return null;
                }

                $ratePlan = RatePlan::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $group->company_id)
                    ->where('is_active', true)
                    ->whereHas('vehicleGroups', fn ($query) => $query->whereKey($group->getKey()))
                    ->orderBy('daily_rate_minor')
                    ->get()
                    ->first(fn (RatePlan $plan): bool => $plan->isEffectiveAt($pickupAt));

                if ($ratePlan === null) {
                    return null;
                }

                $quote = $this->pricing->quote($ratePlan, $pickupAt, $returnAt);

                return [
                    'company' => ['id' => $group->company->getKey(), 'name' => $group->company->display_name, 'slug' => $group->company->slug],
                    'vehicle_group' => [
                        'id' => $group->getKey(), 'uuid' => $group->uuid, 'name' => $group->name, 'category' => $group->category,
                        'make' => $group->make, 'model' => $group->model, 'seats' => $group->seats, 'doors' => $group->doors,
                        'transmission' => $group->transmission, 'fuel_type' => $group->fuel_type, 'features' => $group->features, 'media' => $group->media,
                    ],
                    'rate_plan_id' => $ratePlan->getKey(),
                    'available_units' => $this->availability->availableUnits($group, $pickupAt, $returnAt),
                    'quote' => $quote,
                ];
            })
            ->filter()
            ->values();

        if (($filters['sort'] ?? null) === 'price_asc') {
            $offers = $offers->sortBy('quote.total_minor')->values();
        }
        if (($filters['sort'] ?? null) === 'price_desc') {
            $offers = $offers->sortByDesc('quote.total_minor')->values();
        }
        if (($filters['sort'] ?? null) === 'capacity_desc') {
            $offers = $offers->sortByDesc('available_units')->values();
        }

        $groups->setCollection($offers);

        return response()->json($groups);
    }
}
