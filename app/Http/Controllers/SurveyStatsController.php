<?php

namespace App\Http\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\Survey;
use Illuminate\Support\Facades\DB;

class SurveyStatsController extends Controller
{
    public function getLatestSurveyData()
    {
        $survey = Survey::latest()->first();
        $responses = SubmittedSurvey::where('survey_id', $survey->id)->get();
        $sections = $survey->sections;

        $results = [
            'title' => $survey->title,
            'description' => $survey->description,
            'sections' => [],
        ];

        foreach ($sections as $sectionIndex => $section) {
            $results['sections'][$sectionIndex] = [
                'title' => $section['title'],
                'description' => $section['description'],
                'input_types' => []
            ];

            foreach ($section['input_types'] as $fieldIndex => $field) {
                $fieldLabel = $field['label'];
                $fieldType = $field['type'];
                $options = $field['options'] ?? [];

                $results['sections'][$sectionIndex]['input_types'][$fieldIndex] = [
                    'label' => $fieldLabel,
                    'type' => $fieldType,
                    'options' => $options,
                    'value_percentage' => [],
                    'values' => $fieldType === 'markdown' ? [] : null
                ];

                if ($fieldType === 'checkbox' || $fieldType === 'radio') {
                    $totalResponses = $responses->count();

                    foreach ($options as $option) {
                        $optionSubmittedCount = SubmittedSurvey::where(function($query) use ($fieldLabel, $option) {
                            $escapedOption = addslashes($option); // Escape special characters
                            $query->whereRaw(
                                "JSON_CONTAINS(
                                    JSON_EXTRACT(
                                        survey_response,
                                        '$[*].\"$fieldLabel\".value'
                                    ),
                                    '\"$escapedOption\"'
                                )"
                            );
                        })->count();

                        $results['sections'][$sectionIndex]['input_types'][$fieldIndex]['value_percentage'][$option] = $totalResponses > 0
                            ? number_format(($optionSubmittedCount / $totalResponses) * 100, 2) . '%'
                            : '0%';
                    }
                }

                if ($fieldType === 'markdown') {


                    // Retrieve values from the JSON field
                    $responses = SubmittedSurvey::where(function ($query) use ($fieldLabel) {
                        $query->whereJsonContains(
                            'survey_response',
                            [
                                $fieldLabel => [
                                    'type' => 'markdown',
                                ]
                            ]
                        );
                    })->get();

                    $values = $responses->map(function ($response) use ($fieldLabel) {
                        $surveyResponse = $response->survey_response;
                        $values = [];
                        foreach ($surveyResponse as $entry) {
                            if (!empty($entry[$fieldLabel]['value'])) {
                                $values[] = $entry[$fieldLabel]['value'];
                            }
                        }
                        return $values;
                    })->flatten()->filter()->values()->toArray();

                    // Store values in the results array
                    $results['sections'][$sectionIndex]['input_types'][$fieldIndex]['values'] = $values;
                }

            }
        }

        $totalCharactersWhoCompleted = number_format($responses->count() / Character::count(), 2);

        return view('survey.stats', ['survey' => $results, 'characterWhoCompleted' => $totalCharactersWhoCompleted]);
    }
}
