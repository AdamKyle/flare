<?php

namespace App\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Admin\Requests\SurveyRequest;

class SurveyController extends Controller {
    public function createSurvey(SurveyRequest $request) {
        dump($request->all());

        return response()->json(['message' => 'All done.']);
    }
}
