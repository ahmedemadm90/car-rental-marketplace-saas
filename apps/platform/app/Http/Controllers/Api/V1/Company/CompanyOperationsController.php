<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CompanyOperationsController extends Controller
{
    public function storeDamageReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'severity' => ['required', 'in:minor,moderate,severe'],
            'description' => ['required', 'string'],
            'estimated_cost_minor' => ['required', 'integer', 'min:0'],
            'photos' => ['nullable', 'array'],
        ]);

        $report = DamageReport::query()->create([
            'company_id' => $request->attributes->get('tenant_company_id') ?? 1,
            'vehicle_id' => $data['vehicle_id'],
            'reservation_id' => $data['reservation_id'] ?? null,
            'reporter_id' => $request->user()->getKey(),
            'severity' => $data['severity'],
            'description' => $data['description'],
            'estimated_cost_minor' => $data['estimated_cost_minor'],
            'status' => 'reported',
            'photos' => $data['photos'] ?? [],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Damage report submitted successfully.',
            'data' => $report,
        ], 201);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'category' => ['required', 'string', 'max:64'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'incurred_at' => ['required', 'date'],
        ]);

        $expense = Expense::query()->create([
            'company_id' => $request->attributes->get('tenant_company_id') ?? 1,
            'branch_id' => $data['branch_id'],
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'category' => $data['category'],
            'amount_minor' => $data['amount_minor'],
            'currency' => strtoupper($data['currency']),
            'description' => $data['description'] ?? null,
            'incurred_at' => $data['incurred_at'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Expense recorded successfully.',
            'data' => $expense,
        ], 201);
    }

    public function listInvoices(Request $request): JsonResponse
    {
        $companyId = $request->attributes->get('tenant_company_id') ?? 1;
        $invoices = Invoice::query()->where('company_id', $companyId)->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $invoices,
        ]);
    }
}
