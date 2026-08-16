<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reservations;

use App\Domain\Reservations\ReservationStatus;
use App\Domain\Reservations\Services\ReservationLifecycleService;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Reservation;
use App\Models\WaitlistEntry;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ReservationActionsController extends Controller
{
    public function cancel(Request $request, Reservation $reservation, ReservationLifecycleService $lifecycle): JsonResponse
    {
        if ($reservation->customer_id !== $request->user()->getKey()) {
            abort(403);
        }

        $lifecycle->transition($reservation, ReservationStatus::Cancelled, 'Cancelled by customer.');

        return response()->json([
            'status' => 'success',
            'message' => 'Reservation cancelled successfully.',
            'data' => $reservation->fresh(),
        ]);
    }

    public function reschedule(Request $request, Reservation $reservation, ReservationLifecycleService $lifecycle): JsonResponse
    {
        if ($reservation->customer_id !== $request->user()->getKey()) {
            abort(403);
        }

        $data = $request->validate([
            'pickup_at' => ['required', 'date', 'after:now'],
            'return_at' => ['required', 'date', 'after:pickup_at'],
        ]);

        $pickupAt = CarbonImmutable::parse($data['pickup_at'])->utc();
        $returnAt = CarbonImmutable::parse($data['return_at'])->utc();

        $reservation->update([
            'pickup_at' => $pickupAt,
            'return_at' => $returnAt,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reservation rescheduled successfully.',
            'data' => $reservation->fresh(),
        ]);
    }

    public function signContract(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->customer_id !== $request->user()->getKey()) {
            abort(403);
        }

        $data = $request->validate([
            'signature_data' => ['required', 'string'],
        ]);

        $contract = Contract::query()->updateOrCreate(
            ['reservation_id' => $reservation->getKey()],
            [
                'company_id' => $reservation->company_id,
                'customer_id' => $request->user()->getKey(),
                'uuid' => (string) Str::uuid(),
                'template_version' => 'v1.0',
                'status' => 'signed',
                'disk' => 'local',
                'path' => 'contracts/contract-'.$reservation->getKey().'.pdf',
                'document_hash' => hash('sha256', 'contract-'.$reservation->getKey()),
                'signature_hash' => hash('sha256', $data['signature_data']),
                'signed_at' => now(),
                'signed_ip_address' => $request->ip(),
                'signed_user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Contract signed successfully.',
            'data' => $contract,
        ]);
    }

    public function joinWaitlist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_group_id' => ['required', 'exists:vehicle_groups,id'],
            'pickup_at' => ['required', 'date', 'after:now'],
            'return_at' => ['required', 'date', 'after:pickup_at'],
        ]);

        $entry = WaitlistEntry::query()->create([
            'company_id' => $request->input('company_id', 1),
            'customer_id' => $request->user()->getKey(),
            'vehicle_group_id' => $data['vehicle_group_id'],
            'pickup_at' => CarbonImmutable::parse($data['pickup_at'])->utc(),
            'return_at' => CarbonImmutable::parse($data['return_at'])->utc(),
            'status' => 'waiting',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Added to waitlist successfully.',
            'data' => $entry,
        ], 201);
    }
}
