<?php

namespace App\Game\Survey\Controllers\Api;

use App\Flare\Models\Survey;
use App\Game\Survey\Services\SurveyService;
use App\Http\Controllers\Controller;

class SurveyController extends Controller
{
    public function __construct(private readonly SurveyService $surveyService) {
    }

    public function fetchSurvey(Survey $survey) {
        return response()->json($survey);
    }
}
