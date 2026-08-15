<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reservations;

use App\Domain\Reservations\Actions\CreateReservationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reservations\CreateReservationRequest;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\VehicleGroup;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class ReservationController extends Controller
{
    public function store(CreateReservationRequest $request, CreateReservationAction $createReservation): JsonResponse
    {
        $input = $request->validated();
        $vehicleGroup = VehicleGroup::query()->withoutGlobalScopes()->findOrFail($input['vehicle_group_id']);
        $ratePlan = RatePlan::query()->withoutGlobalScopes()->findOrFail($input['rate_plan_id']);

        if ($vehicleGroup->company_id !== $ratePlan->company_id) {
            return response()->json(['message' => 'Vehicle group and rate plan must belong to the same company.'], 422);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! is_string($idempotencyKey) || ! preg_match('/^[A-Za-z0-9_-]{16,128}$/', $idempotencyKey)) {
            return response()->json(['message' => 'A valid Idempotency-Key header is required.'], 422);
        }

        $cacheKey = 'reservation-request:'.$request->user()->getKey().':'.$idempotencyKey;
        $existing = Cache::get($cacheKey);
        if (is_array($existing)) {
            return response()->json($existing, 201)->header('Idempotency-Replayed', 'true');
        }

        try {
            $reservation = $createReservation->execute($request->user(), $vehicleGroup, $ratePlan, $input);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $payload = ['data' => $this->reservationPayload($reservation)];
        Cache::put($cacheKey, $payload, now()->addHours(24));

        return response()->json($payload, 201);
    }

    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($reservation->customer_id === $request->user()->getKey(), 403);

        return response()->json(['data' => $this->reservationPayload($reservation->loadMissing(['vehicleGroup', 'pickupBranch', 'returnBranch', 'allocations']))]);
    }

    /** @return array<string, mixed> */
    private function reservationPayload(Reservation $reservation): array
    {
        return [
            'id' => $reservation->getKey(),
            'uuid' => $reservation->uuid,
            'reference' => $reservation->reference,
            'status' => $reservation->status->value,
            'pickup_at' => $reservation->pickup_at->toAtomString(),
            'return_at' => $reservation->return_at->toAtomString(),
            'hold_expires_at' => $reservation->hold_expires_at?->toAtomString(),
            'currency' => $reservation->currency,
            'total_minor' => $reservation->total_minor,
            'deposit_minor' => $reservation->deposit_minor,
            'pricing' => $reservation->pricing_snapshot,
            'vehicle_group' => ['id' => $reservation->vehicleGroup->getKey(), 'name' => $reservation->vehicleGroup->name],
            'pickup_branch' => ['id' => $reservation->pickupBranch->getKey(), 'name' => $reservation->pickupBranch->name],
            'return_branch' => ['id' => $reservation->returnBranch->getKey(), 'name' => $reservation->returnBranch->name],
        ];
    }
}
