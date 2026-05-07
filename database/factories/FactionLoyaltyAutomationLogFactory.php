<?php

namespace Database\Factories;

use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactionLoyaltyAutomationLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FactionLoyaltyAutomationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'faction_loyalty_automation_id' => null,
            'fight_logs' => [],
            'crafting_logs' => [],
        ];
    }
}
