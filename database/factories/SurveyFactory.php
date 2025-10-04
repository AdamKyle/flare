<?php

namespace Database\Factories;

use App\Flare\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyFactory extends Factory
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
            'title' => 'Sample Survey',
            'description' => 'Sample Survey',
            'sections' => [
                [
                    'title' => 'Sample Section Title',
                    'description' => 'Sample Section Description',
                    'input_types' => [
                        [
                            'type' => 'radio',
                            'label' => 'Some Radio Label',
                            'options' => [
                                'Option 1',
                                'Option 2',
                                'Option 3',
                            ],
                        ],
                        [
                            'type' => 'checkbox',
                            'label' => 'Some Checkbox Label',
                            'options' => [
                                'Option 1',
                                'Option 2',
                                'Option 3',
                            ],
                        ],
                        [
                            'type' => 'markdown',
                            'label' => 'Some markdown Label',
                        ],
                    ],
                ],
            ],
        ];
    }
}
