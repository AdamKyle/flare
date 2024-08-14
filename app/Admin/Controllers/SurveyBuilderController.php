<?php

namespace App\Admin\Controllers;

use App\Flare\Models\Survey;
use App\Http\Controllers\Controller;

class SurveyBuilderController extends Controller {
    public function createSurvey() {
        return view('admin.survey-builder.survey-builder');
    }

    public function listSurveys() {
        return view('admin.survey-builder.list-surveys');
    }

    public function viewSurvey(Survey $survey) {
        return view('admin.survey-builder.view-survey', ['survey' => $survey]);
    }
}
