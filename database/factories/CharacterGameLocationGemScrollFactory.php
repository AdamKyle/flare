<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterGameLocationGemScrollFactory extends Factory
{
    protected $model = CharacterGameLocationGemScroll::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'game_location_gem_paramter_id' => GameLocationGemParamter::factory(),
            'item_id' => Item::factory()->gemXpScroll(),
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
        ];
    }
}
