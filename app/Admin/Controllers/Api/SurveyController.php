<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\SurveyService;
use App\Http\Controllers\Controller;
use App\Admin\Requests\SurveyRequest;

class SurveyController extends Controller {

    public function __construct(private readonly SurveyService $surveyService) {
    }

    public function createSurvey(SurveyRequest $request) {

        $result = $this->surveyService->createSurvey($request->all());
        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
