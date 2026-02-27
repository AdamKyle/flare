<?php

namespace Database\Factories;

use App\Flare\Models\DelveLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class DelveLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DelveLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => 0,
            'delve_exploration_id' => 0,
            'pack_size' => 1,
            'outcome' => 'survived',
            'fight_data' => ['message_type' => 'message'],
        ];
    }
}
