<?php

namespace Database\Factories;

use App\Flare\Models\GameGemAbility;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\GemAbilityScalingSource;
use App\Game\Gems\Values\GemAbilityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameGemAbilityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameGemAbility::class;

    /**
     * Define the model's default enabled active bonus-damage Gem Ability state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => 'Sample Gem Ability '.$this->faker->unique()->numberBetween(1, 1000000),
            'description' => 'A sample Gem Ability.',
            'ability_type' => GemAbilityType::ACTIVE,
            'effect_type' => GemAbilityEffectType::BONUS_DAMAGE,
            'attack_types' => [AttackType::ATTACK->value],
            'proc_chance' => 0.20,
            'effect_value' => 0.35,
            'scaling_source' => GemAbilityScalingSource::WEAPON_ATTACK,
            'enabled' => true,
        ];
    }
}
