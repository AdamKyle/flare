<?php

namespace Database\Factories;

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
            'last_automation_action' => null,
            'last_automation_action_at' => null,
            'last_fight_monster_id' => null,
            'last_fight_outcome' => null,
            'last_fight_was_bounty_target' => false,
            'last_fight_was_training' => false,
            'last_fight_stalled_attempt' => 0,
            'trained_failed_bounty_monster_id' => null,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
        ];
    }
}
