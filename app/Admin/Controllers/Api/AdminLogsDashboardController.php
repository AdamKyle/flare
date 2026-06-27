<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\AdminLogsDashboardService;
use App\Admin\Transformers\AdminBugChartTransformer;
use App\Admin\Transformers\AdminBugReportTransformer;
use App\Admin\Transformers\AdminLogFileTransformer;
use App\Admin\Transformers\AdminLogPollTransformer;
use App\Admin\Transformers\AdminLogSummaryTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogsDashboardController extends Controller
{
    public function __construct(
        private readonly AdminLogsDashboardService $adminLogsDashboardService,
        private readonly AdminLogFileTransformer $adminLogFileTransformer,
        private readonly AdminLogSummaryTransformer $adminLogSummaryTransformer,
        private readonly AdminLogPollTransformer $adminLogPollTransformer,
        private readonly AdminBugReportTransformer $adminBugReportTransformer,
        private readonly AdminBugChartTransformer $adminBugChartTransformer,
    ) {}

    public function files(): JsonResponse
    {
        return response()->json(array_map(
            fn (array $file): array => $this->adminLogFileTransformer->transform($file),
            $this->adminLogsDashboardService->listFiles(),
        ));
    }

    public function entries(Request $request): JsonResponse
    {
        $fileKey = $request->string('file', 'laravel')->toString();
        $page = max(1, $request->integer('page', 1));
        $severity = $request->string('severity', '')->toString();
        $dateFrom = $request->string('date_from', '')->toString();
        $dateTo = $request->string('date_to', '')->toString();
        $perPage = min($request->integer('per_page', 10), 100);

        return response()->json(
            $this->adminLogsDashboardService->entries($fileKey, $page, $severity, $dateFrom, $dateTo, $perPage),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $fileKey = $request->string('file', 'laravel')->toString();
        $severity = $request->string('severity', '')->toString();
        $dateFrom = $request->string('date_from', '')->toString();
        $dateTo = $request->string('date_to', '')->toString();

        return response()->json(
            $this->adminLogSummaryTransformer->transform(
                $this->adminLogsDashboardService->summary($fileKey, $severity, $dateFrom, $dateTo),
            ),
        );
    }

    public function poll(Request $request): JsonResponse
    {
        $fileKey = $request->string('file', 'laravel')->toString();
        $severity = $request->string('severity', '')->toString();
        $dateFrom = $request->string('date_from', '')->toString();
        $dateTo = $request->string('date_to', '')->toString();

        return response()->json(
            $this->adminLogPollTransformer->transform(
                $this->adminLogsDashboardService->poll($fileKey, $severity, $dateFrom, $dateTo),
            ),
        );
    }

    public function bugs(): JsonResponse
    {
        return response()->json(array_map(
            fn (array $bug): array => $this->adminBugReportTransformer->transform($bug),
            $this->adminLogsDashboardService->bugReports(),
        ));
    }

    public function bugChart(Request $request): JsonResponse
    {
        return response()->json(array_map(
            fn (array $row): array => $this->adminBugChartTransformer->transform($row),
            $this->adminLogsDashboardService->bugChart($request->integer('days', 30)),
        ));
    }
}
