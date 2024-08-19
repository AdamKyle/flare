<?php

namespace App\Flare\Services;

use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\Survey;
use App\Flare\Models\SurveySnapshot;
use Carbon\Carbon;
use PHPUnit\Event\Telemetry\Snapshot;

class CreateSurveySnapshot {

    private array $survey = [];

    private int $surveySnapShotId = 0;

    private Carbon|null $createdAt = null;

    /**
     * Execute the process of creating a survey snapshot.
     *
     * @return CreateSurveySnapshot
     */
    public function createSnapShop(): CreateSurveySnapshot
    {
        $survey = $this->getLatestSurvey();
        $responses = $this->getSurveyResponses($survey->id);
        $sections = $survey->sections;

        $results = $this->initializeResults($survey);

        foreach ($sections as $sectionIndex => $section) {
            $results['sections'][$sectionIndex] = $this->processSection($section, $responses);
        }

        $surveySnapShot = SurveySnapshot::updateOrCreate(['survey_id' => $survey->id], [
            'survey_id' => $survey->id,
            'snap_shot_data' => $results
        ]);

        $this->surveySnapShotId = $surveySnapShot->id;

        $this->createdAt = $surveySnapShot->created_at;

        $this->survey = $results;

        return $this;
    }

    /**
     * get the survey results.
     *
     * @return array
     */
    public function getSurvey(): array {
        return $this->survey;
    }

    /**
     * Get the survey snapshot id.
     *
     * @return int
     */
    public function getSurveySnapShotId(): int {
        return $this->surveySnapShotId;
    }

    /**
     * Get when the survey snapshot was created.
     *
     * @return Carbon|null
     */
    public function getCreatedAt(): Carbon|null {
        return $this->createdAt;
    }

    /**
     * Get the latest survey.
     *
     * @return Survey
     */
    private function getLatestSurvey(): Survey
    {
        return Survey::latest()->first();
    }

    /**
     * Get responses for the given survey ID.
     *
     * @param int $surveyId
     * @return \Illuminate\Support\Collection
     */
    private function getSurveyResponses(int $surveyId)
    {
        return SubmittedSurvey::where('survey_id', $surveyId)->get();
    }

    /**
     * Initialize the results array.
     *
     * @param Survey $survey
     * @return array
     */
    private function initializeResults(Survey $survey): array
    {
        return [
            'title' => $survey->title,
            'description' => $survey->description,
            'sections' => [],
        ];
    }

    /**
     * Process a section of the survey.
     *
     * @param array $section
     * @param \Illuminate\Support\Collection $responses
     * @return array
     */
    private function processSection(array $section, $responses): array
    {
        $sectionData = [
            'title' => $section['title'],
            'description' => $section['description'],
            'input_types' => [],
        ];

        foreach ($section['input_types'] as $fieldIndex => $field) {
            $sectionData['input_types'][$fieldIndex] = $this->processField($field, $responses);
        }

        return $sectionData;
    }

    /**
     * Process a field within a section.
     *
     * @param array $field
     * @param \Illuminate\Support\Collection $responses
     * @return array
     */
    private function processField(array $field, $responses): array
    {
        $fieldData = $this->initializeFieldData($field);

        if ($this->isSelectableField($field['type'])) {
            $fieldData['value_percentage'] = $this->calculateValuePercentage($field, $responses);
        }

        if ($field['type'] === 'markdown') {
            $fieldData['values'] = $this->retrieveMarkdownValues($field['label']);
        }

        return $fieldData;
    }

    /**
     * Initialize the field data array.
     *
     * @param array $field
     * @return array
     */
    private function initializeFieldData(array $field): array
    {
        return [
            'label' => $field['label'],
            'type' => $field['type'],
            'options' => $field['options'] ?? [],
            'value_percentage' => [],
            'values' => $field['type'] === 'markdown' ? [] : null,
        ];
    }

    /**
     * Determine if the field is a selectable type (checkbox or radio).
     *
     * @param string $fieldType
     * @return bool
     */
    private function isSelectableField(string $fieldType): bool
    {
        return in_array($fieldType, ['checkbox', 'radio']);
    }

    /**
     * Calculate the value percentage for selectable fields.
     *
     * @param array $field
     * @param \Illuminate\Support\Collection $responses
     * @return array
     */
    private function calculateValuePercentage(array $field, $responses): array
    {
        $percentages = [];
        $totalResponses = $responses->count();

        foreach ($field['options'] as $option) {
            $optionSubmittedCount = $this->getOptionSubmittedCount($field['label'], $option);

            $percentages[$option] = $this->formatPercentage($optionSubmittedCount, $totalResponses);
        }

        return $percentages;
    }

    /**
     * Get the count of responses that selected a given option.
     *
     * @param string $fieldLabel
     * @param string $option
     * @return int
     */
    private function getOptionSubmittedCount(string $fieldLabel, string $option): int
    {
        return SubmittedSurvey::where(function($query) use ($fieldLabel, $option) {
            $escapedOption = addslashes($option);
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
    }

    /**
     * Format the percentage for an option.
     *
     * @param int $count
     * @param int $total
     * @return string
     */
    private function formatPercentage(int $count, int $total): string
    {
        return $total > 0
            ? number_format(($count / $total) * 100, 2) . '%'
            : '0%';
    }

    /**
     * Retrieve values for markdown fields.
     *
     * @param string $fieldLabel
     * @return array
     */
    private function retrieveMarkdownValues(string $fieldLabel): array
    {
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

        return $responses->map(function ($response) use ($fieldLabel) {
            return $this->extractMarkdownValues($response->survey_response, $fieldLabel);
        })->flatten()->filter()->values()->toArray();
    }

    /**
     * Extract markdown values from the survey response.
     *
     * @param array $surveyResponse
     * @param string $fieldLabel
     * @return array
     */
    private function extractMarkdownValues(array $surveyResponse, string $fieldLabel): array
    {
        $values = [];

        foreach ($surveyResponse as $entry) {
            if (!empty($entry[$fieldLabel]['value'])) {
                $values[] = $entry[$fieldLabel]['value'];
            }
        }

        return $values;
    }
}
