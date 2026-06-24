<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\AdminLogsDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogsDashboardController extends Controller
{
    public function __construct(
        private readonly AdminLogsDashboardService $adminLogsDashboardService,
    ) {}

    public function files(): JsonResponse
    {
        return response()->json($this->adminLogsDashboardService->listFiles());
    }

    public function entries(Request $request): JsonResponse
    {
        $fileKey = $request->string('file', 'laravel')->toString();
        $page = max(1, $request->integer('page', 1));
        $severity = $request->string('severity', '')->toString();
        $dateFrom = $request->string('date_from', '')->toString();
        $dateTo = $request->string('date_to', '')->toString();

        return response()->json(
            $this->adminLogsDashboardService->entries($fileKey, $page, $severity, $dateFrom, $dateTo),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $fileKey = $request->string('file', 'laravel')->toString();
        $severity = $request->string('severity', '')->toString();
        $dateFrom = $request->string('date_from', '')->toString();
        $dateTo = $request->string('date_to', '')->toString();

        return response()->json(
            $this->adminLogsDashboardService->summary($fileKey, $severity, $dateFrom, $dateTo),
        );
    }
}
