<?php

namespace App\Admin\PassiveSkills\Controllers\Api;

use App\Admin\PassiveSkills\Requests\PassiveSkillImportRequest;
use App\Admin\PassiveSkills\Services\PassiveSkillExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PassiveSkillImportController extends Controller
{
    /**
     * @param PassiveSkillExcelService $passiveSkillExcelService
     */
    public function __construct(private readonly PassiveSkillExcelService $passiveSkillExcelService) {}

    /**
     * Import the validated Passive Skills workbook and return the success response.
     *
     * @param PassiveSkillImportRequest $request
     * @return JsonResponse
     */
    public function __invoke(PassiveSkillImportRequest $request): JsonResponse
    {
        $this->passiveSkillExcelService->import($request->file('passives_import'));

        return response()->json([
            'message' => 'Passive Skills imported successfully.',
        ]);
    }
}
