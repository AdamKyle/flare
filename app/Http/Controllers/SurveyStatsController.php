<?php

namespace App\Http\Controllers;

use App\Flare\Github\Services\Markdown;
use App\Flare\Models\Character;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\SurveySnapshot;
use App\Flare\Services\CreateSurveySnapshot;
use App\Game\Events\Values\EventType;
use Illuminate\Http\Request;

class SurveyStatsController extends Controller
{
    public function __construct(private readonly CreateSurveySnapshot $createSurveySnapshot, private readonly Markdown $markdown) {}

    public function getLatestSurveyData()
    {
        $scheduledEvent = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first();

        if (! is_null($scheduledEvent)) {
            return view('survey.stats', ['surveyExists' => false]);
        }

        $surveySnapShot = SurveySnapshot::latest()->first();
        $surveysSubmittedCount = $surveySnapShot->submitted_survey_count;

        if ($surveysSubmittedCount <= 0) {
            return view('survey.stats', ['surveyExists' => false]);
        }

        $surveySnapShotData = $surveySnapShot->snap_shot_data;

        $totalCharactersWhoCompleted = number_format($surveysSubmittedCount / Character::count(), 2);

        return view('survey.stats', ['survey' => $surveySnapShotData, 'characterWhoCompleted' => $totalCharactersWhoCompleted, 'surveySnapShotId' => $this->createSurveySnapshot->getSurveySnapShotId(), 'dateGenerated' => $surveySnapShot->created_at, 'surveyExists' => true]);
    }

    public function getResponseDataForQuestion(Request $request, SurveySnapshot $surveySnapshot)
    {
        $label = $request->get('survey_question');

        $values = $surveySnapshot->whereRaw(
            "JSON_SEARCH(
                JSON_EXTRACT(snap_shot_data, '$.sections[*].input_types[*].label'),
                'one',
                ?
            ) IS NOT NULL", [$label]
        )->whereRaw(
            "JSON_SEARCH(
                JSON_EXTRACT(snap_shot_data, '$.sections[*].input_types[*].type'),
                'one',
                'markdown'
            ) IS NOT NULL"
        )->get()->flatMap(function ($snapshot) use ($label) {
            return collect($snapshot->snap_shot_data['sections'])->flatMap(function ($section) use ($label) {
                return collect($section['input_types'])->filter(function ($inputType) use ($label) {
                    return $inputType['type'] === 'markdown' && $inputType['label'] === $label;
                })->flatMap(function ($inputType) {
                    return collect($inputType['values'])->map(function ($value) {
                        return $this->markdown->convertToHtml($value);
                    });
                });
            });
        })->toArray();

        return view('survey.responses', ['responses' => $values, 'questionLabel' => $label]);
    }

    public function getCreatorResponse()
    {
        return view('survey.creators-response');
    }
}
