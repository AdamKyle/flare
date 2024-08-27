<?php

namespace Database\Factories;

use App\Flare\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmittedSurveyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Survey::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'character_id' => null,
            'survey_id' => null,
            'survey_response' => [
                [
                    'Some Radio label' => ['value' => 'Option 1', 'type' => 'radio'],
                    'Some Checkbox label' => ['value' => 'Option 1', 'type' => 'checkbox'],
                    'Some markdown label' => ['value' => 'Some content', 'type' => 'markdown'],
                ]
            ]
        ];
    }
}


