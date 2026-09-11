<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterGameMapGemScrollFactory extends Factory
{
    protected $model = CharacterGameMapGemScroll::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'game_map_gem_paramter_id' => GameMapGemParamter::factory(),
            'item_id' => Item::factory()->gemXpScroll(),
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
        ];
    }
}
