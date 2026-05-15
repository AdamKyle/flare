<?php

namespace Database\Factories;

use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyaltyAutomation;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactionLoyaltyAutomationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FactionLoyaltyAutomation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_automation_id' => null,
            'character_id' => null,
            'faction_loyalty_npc_id' => null,
            'failed_bounty_monster_id' => null,
            'failed_crafting_item_id' => null,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
        ];
    }
}
