<?php

namespace App\Game\Survey\Validator;

use App\Flare\Models\Survey;
use Illuminate\Support\Collection;

/**
 * Class SurveyValidator
 *
 * Validates user input for surveys against the survey sections.
 */
class SurveyValidator
{
    private Collection $sections;

    /**
     * @return $this
     */
    public function setSurveySections(Survey $survey): SurveyValidator
    {
        $this->sections = collect($survey->sections);

        return $this;
    }

    /**
     * Validate the provided survey input data.
     */
    public function validate(array $input): bool
    {
        foreach ($this->sections as $index => $section) {
            if (! isset($input[$index])) {
                return false;
            }

            $sectionInput = $input[$index];

            if (! $this->validateSection($section, $sectionInput)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate an individual section of the survey.
     */
    private function validateSection(array $section, array $sectionInput): bool
    {
        foreach ($section['input_types'] as $inputType) {
            if ($inputType['type'] === 'markdown') {
                continue;
            }

            $label = $inputType['label'];

            if (! isset($sectionInput[$label]['value'])) {
                return false;
            }

            $value = $sectionInput[$label]['value'];

            if (! $this->validateInputType($inputType, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate an individual input field.
     *
     * @param  mixed  $value
     */
    private function validateInputType(array $inputType, $value): bool
    {
        switch ($inputType['type']) {
            case 'radio':
                return in_array($value, $inputType['options'], true);
            case 'checkbox':
                return $this->validateCheckboxOptions($inputType['options'], $value);
            default:
                return false;
        }
    }

    /**
     * Validate the options selected in a checkbox field.
     */
    private function validateCheckboxOptions(array $options, array $selectedValues): bool
    {

        foreach ($selectedValues as $selectedValue) {
            if (! in_array($selectedValue, $options, true)) {
                return false;
            }
        }

        return true;
    }
}
