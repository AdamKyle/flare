<?php

namespace App\Admin\Controllers;

use App\Admin\Requests\SurveyImport;
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

    public function exportInfo()
    {
        return response()->view('admin.survey-builder.export');
    }

    public function importInfo()
    {
        return response()->view('admin.survey-builder.import');
    }

    public function export() {
        return response()->attachment(Survey::all(), 'surveys');
    }

    public function import(SurveyImport $request)
    {

        $data = json_decode(trim($request->file('info_import')->get()), true);

        foreach ($data as $modelEntry) {
            Survey::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }

        return response()->redirectToRoute('admin.info-management')->with('success', 'Surveys have been imported.');
    }
}
