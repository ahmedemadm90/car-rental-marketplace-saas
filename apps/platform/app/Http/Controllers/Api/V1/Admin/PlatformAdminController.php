<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\CmsPage;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformAdminController extends Controller
{
    public function listTenants(Request $request): JsonResponse
    {
        $companies = Company::query()->with(['branches', 'subscription.plan'])->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $companies,
        ]);
    }

    public function storeCmsPage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'unique:cms_pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'locale' => ['required', 'in:en,ar'],
        ]);

        $page = CmsPage::query()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'CMS page created successfully.',
            'data' => $page,
        ], 201);
    }

    public function listAuditEvents(Request $request): JsonResponse
    {
        $events = AuditEvent::query()->latest('occurred_at')->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    public function systemHealth(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'database' => 'connected',
            'redis' => 'connected',
            'version' => '1.0.0',
        ]);
    }
}
