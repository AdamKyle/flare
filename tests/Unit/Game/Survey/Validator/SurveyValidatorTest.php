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

    public function setUp(): void
    {
        parent::setUp();

        $this->surveryValidator = resolve(SurveyValidator::class);

        $this->survey = $this->createSurvey();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->surveryValidator = null;

        $this->survey = null;
    }

    public function testSectionLabelDoesNotHaveAValue()
    {
        $isValid = $this->surveryValidator->setSurveySections($this->survey)->validate([[
            'Some Radio Label' => ['type' => 'radio'],
            'Some Checkbox Label' => ['type' => 'checkbox'],
            'Some markdown Label' => ['type' => 'markdown'],
        ]]);

        $this->assertFalse($isValid);
    }

    public function testSectionIsInvalidWhenUsingInvalidType()
    {

        $survey = $this->createSurvey([
            'sections' =>  [[
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
                        ]
                    ],
                ]
            ]]
        ]);

        $isValid = $this->surveryValidator->setSurveySections($survey)->validate([[
            'Some Select Label' => ['value' => 'Sample', 'type' => 'select'],
        ]]);

        $this->assertFalse($isValid);
    }

    public function testSectionIsInvalidWhenCheckBoxOptionIsNotAValidSelection()
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
                        ]
                    ],
                ]
            ]]
        ]);

        $isValid = $this->surveryValidator->setSurveySections($survey)->validate([[
            'Some Checkbox Label' => ['value' => ['Sample'], 'type' => 'checkbox'],
        ]]);

        $this->assertFalse($isValid);
    }

    public function testSectionsAreValid()
    {
        $isValid = $this->surveryValidator->setSurveySections($this->survey)->validate([[
            'Some Radio Label' => ['value' => 'Option 1', 'type' => 'radio'],
            'Some Checkbox Label' => ['value' => ['Option 1'], 'type' => 'checkbox'],
            'Some markdown Label' => ['value' => 'Some content', 'type' => 'markdown'],
        ]]);

        $this->assertTrue($isValid);
    }
}
