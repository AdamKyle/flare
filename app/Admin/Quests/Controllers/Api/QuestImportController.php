<?php

namespace App\Admin\Quests\Controllers\Api;

use App\Admin\Quests\Requests\QuestImportRequest;
use App\Admin\Quests\Services\QuestExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QuestImportController extends Controller
{
    /**
     * @param  QuestExcelService  $questExcelService  Quest Excel export/import service.
     */
    public function __construct(
        private readonly QuestExcelService $questExcelService,
    ) {}

    /**
     * Import validated Quests and return the resulting success or failure response.
     *
     * @param  QuestImportRequest  $request  Validated Quest import request.
     * @return JsonResponse Import result JSON response.
     */
    public function __invoke(QuestImportRequest $request): JsonResponse
    {
        $result = $this->questExcelService->import($request->file('quests_import'));

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
