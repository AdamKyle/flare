<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Services\SurveyService;
use App\Flare\Models\Survey;
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

    public function saveSurvey(SurveyRequest $request, Survey $survey) {
        $result = $this->surveyService->saveSurvey($survey, $request->all());
        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchSurvey(Survey $survey) {
        return response()->json($survey);
    }
}
