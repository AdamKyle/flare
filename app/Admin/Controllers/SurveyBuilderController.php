<?php

namespace App\Admin\Controllers;

use App\Flare\Models\Survey;
use App\Http\Controllers\Controller;

class SurveyBuilderController extends Controller {
    public function createSurvey() {
        return view('admin.survey-builder.survey-builder');
    }

    public function editSurvey(Survey $survey) {
        return view('admin.survey-builder.edit-survey', [
            'surveyId' => $survey->id,
        ]);
    }

    public function listSurveys() {
        return view('admin.survey-builder.list-surveys');
    }

    public function viewSurvey(Survey $survey) {
        return view('admin.survey-builder.view-survey', ['survey' => $survey]);
    }

    public function deleteSurvey(Survey $survey) {
        $survey->delete();

        return redirect()->route('admin.surveys')->with('success', 'Survey has been deleted');
    }
}
