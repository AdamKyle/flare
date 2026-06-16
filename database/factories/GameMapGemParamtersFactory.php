<?php

namespace Database\Factories;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamters;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMapGemParamtersFactory extends Factory
{
    protected $model = GameMapGemParamters::class;

    public function definition(): array
    {
        return [
            'game_map_id' => GameMap::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => 'A generated gem parameter description.',
            'character_xp_bonus_range' => '0.01-1.0',
            'gold_gain_range' => '0.01-1.0',
            'crafting_skill_ids' => [],
            'crafting_skill_bonus_range' => '0.01-1.0',
            'unique_item_drop_chance_increase_range' => '0.01-1.0',
            'mythic_item_drop_chance_increase_range' => '0.01-1.0',
            'cosmic_item_drop_chance_increase_range' => '0.01-1.0',
            'ascended_item_drop_chance_increase_range' => '0.01-1.0',
            'character_power_reduction_range' => '0.01-1.0',
            'monster_atonement' => GemTypeValue::FIRE,
            'monster_atonement_range' => '0.01-1.0',
        ];
    }
}
