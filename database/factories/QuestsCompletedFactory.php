<?php

namespace Database\Factories;

use App\Flare\Models\QuestsCompleted;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestsCompletedFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QuestsCompleted::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'quest_id' => null,
        ];
    }
}
