<?php

namespace Database\Factories;

use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\Gem;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterGemModifierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterGemModifier::class;

    /**
     * Define the model's default raw Strength modifier state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'gem_id' => Gem::factory()->state(['domain' => Gem::DOMAIN_CHARACTER]),
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::STRENGTH,
            'amount' => 10,
            'game_gem_ability_id' => null,
        ];
    }
}
