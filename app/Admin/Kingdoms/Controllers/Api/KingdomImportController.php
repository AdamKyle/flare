<?php

namespace App\Admin\Kingdoms\Controllers\Api;

use App\Admin\Kingdoms\Requests\KingdomImportRequest;
use App\Admin\Kingdoms\Services\KingdomExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KingdomImportController extends Controller
{
    /**
     * @param KingdomExcelService $kingdomExcelService
     */
    public function __construct(private readonly KingdomExcelService $kingdomExcelService) {}

    /**
     * Import the validated Kingdom workbook and return the import outcome.
     *
     * @param KingdomImportRequest $request
     * @return JsonResponse
     */
    public function __invoke(KingdomImportRequest $request): JsonResponse
    {
        $result = $this->kingdomExcelService->import($request->file('kingdom_import'));
        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
