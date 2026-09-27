<?php

namespace App\Admin\Skills\Controllers\Api;

use App\Admin\Skills\Requests\SkillImportRequest;
use App\Admin\Skills\Services\SkillExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SkillImportController extends Controller
{
    /**
     * @param SkillExcelService $skillExcelService
     */
    public function __construct(private readonly SkillExcelService $skillExcelService) {}

    /**
     * Import the validated Skills workbook and return the success response.
     *
     * @param SkillImportRequest $request
     * @return JsonResponse
     */
    public function __invoke(SkillImportRequest $request): JsonResponse
    {
        $this->skillExcelService->import($request->file('skills_import'));

        return response()->json([
            'message' => 'Skills imported successfully.',
        ]);
    }
}
