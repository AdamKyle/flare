<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\AdminLogsDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

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
        $dateFrom = $request->string('date_from', now()->subDay()->toDateString())->toString();
        $dateTo = $request->string('date_to', now()->toDateString())->toString();
        $cursor = $request->string('cursor', '')->toString();

        try {
            return response()->json(
                $this->adminLogsDashboardService->entries($fileKey, $page, $severity, $dateFrom, $dateTo, $cursor),
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => $throwable->getMessage(),
            ], 500);
        }
    }

    public function entryDetail(Request $request): JsonResponse
    {
        $detail = $this->adminLogsDashboardService->entryDetail(
            $request->string('file')->toString(),
            $request->string('detail_id')->toString(),
        );

        if (is_null($detail)) {
            return response()->json(['message' => 'The requested log detail is no longer available.'], 404);
        }

        return response()->json($detail);
    }

    public function poll(Request $request): JsonResponse
    {
        $fileKey = $request->string('file', 'laravel')->toString();
        $severity = $request->string('severity', '')->toString();
        $dateFrom = $request->string('date_from', '')->toString();
        $dateTo = $request->string('date_to', '')->toString();

        return response()->json(
            $this->adminLogsDashboardService->poll($fileKey, $severity, $dateFrom, $dateTo),
        );
    }

    public function bugs(): JsonResponse
    {
        return response()->json($this->adminLogsDashboardService->bugReports());
    }

    public function bugChart(Request $request): JsonResponse
    {
        return response()->json($this->adminLogsDashboardService->bugChart($request->integer('days', 30)));
    }
}
