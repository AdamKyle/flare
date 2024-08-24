<?php

namespace Database\Factories;

use App\Flare\Models\SecurityQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SecurityQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => null,
            'question' => null,
            'answer' => null,
        ];
    }
}
