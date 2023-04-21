<?php

namespace Database\Factories;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\GameBuildingUnit;

class CharacterAutomationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterAutomation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id'                   => null,
            'monster_id'                     => null,
            'type'                           => AutomationType::EXPLORING,
            'started_at'                     => now(),
            'completed_at'                   => now()->addSeconds(5),
            'move_down_monster_list_every'   => null,
            'previous_level'                 => null,
            'current_level'                  => null,
            'attack_type'                    => null,
        ];
    }
}
