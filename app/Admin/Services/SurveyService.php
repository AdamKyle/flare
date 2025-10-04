<?php

namespace App\Admin\Services;

use App\Flare\Models\Survey;
use App\Game\Core\Traits\ResponseBuilder;

class SurveyService
{
    use ResponseBuilder;

    public function createSurvey(array $params): array
    {
        Survey::create($params);

        return $this->successResult([
            'message' => 'Survey: '.$params['title'].' created.',
        ]);
    }

    public function saveSurvey(Survey $survey, array $params): array
    {
        $survey->update($params);

        $survey = $survey->refresh();

        return $this->successResult([
            'message' => 'Survey: '.$params['title'].' updated.',
            'title' => $survey->title,
            'description' => $survey->description,
            'sections' => $survey->sections,
        ]);
    }
}
