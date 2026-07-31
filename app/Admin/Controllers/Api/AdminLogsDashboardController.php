<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\AdminLogsDashboardService;
use App\Admin\Transformers\AdminBugChartTransformer;
use App\Admin\Transformers\AdminBugReportTransformer;
use App\Admin\Transformers\AdminLogEntryTransformer;
use App\Admin\Transformers\AdminLogFileTransformer;
use App\Admin\Transformers\AdminLogPollTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdminLogsDashboardController extends Controller
{
    public function __construct(
        private readonly AdminLogsDashboardService $adminLogsDashboardService,
        private readonly AdminLogFileTransformer $adminLogFileTransformer,
        private readonly AdminLogEntryTransformer $adminLogEntryTransformer,
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

        return response()->json($this->adminLogEntryTransformer->transform($detail));
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
