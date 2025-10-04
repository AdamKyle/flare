<?php

namespace Tests\Unit\Game\Survey\Validator;

use App\Flare\Models\Survey;
use App\Game\Survey\Validator\SurveyValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateSurvey;

class SurveyValidatorTest extends TestCase
{
    use CreateSurvey, RefreshDatabase;

    private ?SurveyValidator $surveryValidator;

    private ?Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->surveryValidator = resolve(SurveyValidator::class);

        $this->survey = $this->createSurvey();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->surveryValidator = null;

        $this->survey = null;
    }

    public function test_section_label_does_not_have_a_value()
    {
        $isValid = $this->surveryValidator->setSurveySections($this->survey)->validate([[
            'Some Radio Label' => ['type' => 'radio'],
            'Some Checkbox Label' => ['type' => 'checkbox'],
            'Some markdown Label' => ['type' => 'markdown'],
        ]]);

        $this->assertFalse($isValid);
    }

    public function test_section_is_invalid_when_using_invalid_type()
    {

        $survey = $this->createSurvey([
            'sections' => [[
                'title' => 'Sample Section Title',
                'description' => 'Sample Section Description',
                'input_types' => [
                    [
                        'type' => 'select',
                        'label' => 'Some Select Label',
                        'options' => [
                            'Option 1',
                            'Option 2',
                            'Option 3',
                        ],
                    ],
                ],
            ]],
        ]);

        $isValid = $this->surveryValidator->setSurveySections($survey)->validate([[
            'Some Select Label' => ['value' => 'Sample', 'type' => 'select'],
        ]]);

        $this->assertFalse($isValid);
    }

    public function test_section_is_invalid_when_check_box_option_is_not_a_valid_selection()
    {
        $survey = $this->createSurvey([
            'sections' => [[
                'title' => 'Sample Section Title',
                'description' => 'Sample Section Description',
                'input_types' => [
                    [
                        'type' => 'checkbox',
                        'label' => 'Some Checkbox Label',
                        'options' => [
                            'Option 1',
                            'Option 2',
                            'Option 3',
                        ],
                    ],
                ],
            ]],
        ]);

        $isValid = $this->surveryValidator->setSurveySections($survey)->validate([[
            'Some Checkbox Label' => ['value' => ['Sample'], 'type' => 'checkbox'],
        ]]);

        $this->assertFalse($isValid);
    }

    public function test_sections_are_valid()
    {
        $isValid = $this->surveryValidator->setSurveySections($this->survey)->validate([[
            'Some Radio Label' => ['value' => 'Option 1', 'type' => 'radio'],
            'Some Checkbox Label' => ['value' => ['Option 1'], 'type' => 'checkbox'],
            'Some markdown Label' => ['value' => 'Some content', 'type' => 'markdown'],
        ]]);

        $this->assertTrue($isValid);
    }
}
