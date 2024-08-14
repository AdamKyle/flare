<?php

namespace App\Admin\Services;

use App\Flare\Models\Survey;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Flare\Models\Character;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Core\Values\FeedbackType;

class SurveyService {

    use ResponseBuilder;

    public function createSurvey(array $params): array {
        Survey::create($params);

        return $this->successResult([
            'message' => 'Survey: ' . $params['title'] . ' created.',
        ]);
    }
}
