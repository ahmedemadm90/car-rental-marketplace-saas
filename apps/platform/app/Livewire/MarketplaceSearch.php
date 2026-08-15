<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Marketplace\Services\AvailabilityService;
use App\Domain\Pricing\Services\PricingService;
use App\Models\Branch;
use App\Models\RatePlan;
use App\Models\VehicleGroup;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class MarketplaceSearch extends Component
{
    public string $pickupAt;

    public string $returnAt;

    public ?int $pickupBranchId = null;

    public ?int $returnBranchId = null;

    public ?string $category = null;

    public ?string $transmission = null;

    public ?string $fuelType = null;

    public ?int $minimumSeats = null;

    public function mount(): void
    {
        $this->pickupAt = now()->addDay()->startOfHour()->format('Y-m-d\\TH:i');
        $this->returnAt = now()->addDays(3)->startOfHour()->format('Y-m-d\\TH:i');
        $firstBranch = Branch::query()->withoutGlobalScopes()->where('is_active', true)->orderBy('name')->first();
        $this->pickupBranchId = $firstBranch?->getKey();
        $this->returnBranchId = $firstBranch?->getKey();
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'pickupAt' => ['required', 'date', 'after:now'],
            'returnAt' => ['required', 'date', 'after:pickupAt'],
            'pickupBranchId' => ['required', 'exists:branches,id'],
            'returnBranchId' => ['required', 'exists:branches,id'],
            'category' => ['nullable', 'string', 'max:64'],
            'transmission' => ['nullable', 'in:automatic,manual'],
            'fuelType' => ['nullable', 'in:petrol,diesel,hybrid,electric'],
            'minimumSeats' => ['nullable', 'integer', 'min:1', 'max:15'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function render(AvailabilityService $availability, PricingService $pricing): View
    {
        $branches = Branch::query()->withoutGlobalScopes()->where('is_active', true)->orderBy('name')->get();
        $offers = collect();

        if ($this->pickupBranchId !== null && $this->returnBranchId !== null && $this->pickupAt !== '' && $this->returnAt !== '') {
            $pickupAt = CarbonImmutable::parse($this->pickupAt)->utc();
            $returnAt = CarbonImmutable::parse($this->returnAt)->utc();

            if ($returnAt->greaterThan($pickupAt)) {
                $offers = VehicleGroup::query()
                    ->withoutGlobalScopes()
                    ->with('company:id,display_name,slug')
                    ->where('is_public', true)
                    ->where('is_active', true)
                    ->when($this->category, fn ($query) => $query->where('category', $this->category))
                    ->when($this->transmission, fn ($query) => $query->where('transmission', $this->transmission))
                    ->when($this->fuelType, fn ($query) => $query->where('fuel_type', $this->fuelType))
                    ->when($this->minimumSeats, fn ($query) => $query->where('seats', '>=', $this->minimumSeats))
                    ->orderBy('name')
                    ->get()
                    ->map(function (VehicleGroup $group) use ($availability, $pricing, $pickupAt, $returnAt): ?array {
                        if (! $availability->isAvailable($group, $pickupAt, $returnAt)) {
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

                        return ['group' => $group, 'rate_plan' => $ratePlan, 'quote' => $pricing->quote($ratePlan, $pickupAt, $returnAt), 'available_units' => $availability->availableUnits($group, $pickupAt, $returnAt)];
                    })
                    ->filter()
                    ->values();
            }
        }

        return view('livewire.marketplace-search', ['offers' => $offers, 'branches' => $branches]);
    }
}
