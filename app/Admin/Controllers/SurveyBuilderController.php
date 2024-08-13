<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;

class SurveyBuilderController extends Controller {

    public function createSurvey() {
        return view('admin.survey-builder.survey-builder');
    }
}
