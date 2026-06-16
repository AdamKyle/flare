<?php

namespace Database\Factories;

use App\Flare\Models\GameLocationGemParamters;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameLocationGemParamtersFactory extends Factory
{
    protected $model = GameLocationGemParamters::class;

    public function definition(): array
    {
        return [
            'location_id' => function (): int {
                $gameMap = GameMap::first() ?? GameMap::factory()->create();

                return Location::factory()->create(['game_map_id' => $gameMap->id])->id;
            },
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
            'monster_atonement' => GemTypeValue::FIRE,
            'monster_atonement_range' => '0.01-1.0',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (GameLocationGemParamters $gemParams) {
            Item::factory()->create([
                'type' => 'quest',
                'drop_location_id' => $gemParams->location_id,
            ]);
        });
    }
}
