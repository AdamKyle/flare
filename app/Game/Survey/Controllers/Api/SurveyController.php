<?php

namespace App\Game\Survey\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Survey;
use App\Game\Survey\Services\SurveyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(private readonly SurveyService $surveyService) {}

    public function fetchSurvey(Survey $survey)
    {
        return response()->json($survey);
    }

    public function saveAnswers(Survey $survey, Character $character, Request $request)
    {
        $response = $this->surveyService->saveSurvey($character, $survey, $request->all());

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
