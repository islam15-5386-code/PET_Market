<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    // ── GET /api/admin/dashboard ──────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $snapshot = $this->dashboardService->getSnapshot();

        return response()->json([
            'success' => true,
            'data'    => $snapshot,
        ]);
    }

    // ── GET /api/admin/dashboard/revenue ──────────────────────────────────────

    public function revenue(): JsonResponse
    {
        $data = $this->dashboardService->getRevenueChart();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
