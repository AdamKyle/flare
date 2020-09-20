<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\AdventureLog;

class AdventureLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AdventureLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id'         => null,
            'adventure_id'         => null,
            'complete'             => null,
            'in_progress'          => null,
            'last_completed_level' => null,
            'logs'                 => null,
            'rewards'              => null,
        ];
    }
}
