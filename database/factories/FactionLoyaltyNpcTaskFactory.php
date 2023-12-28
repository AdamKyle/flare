<?php

namespace Database\Factories;

use App\Flare\Models\FactionLoyaltyNpcTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactionLoyaltyNpcTaskFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FactionLoyaltyNpcTask::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'faction_loyalty_id'       => null,
            'faction_loyalty_npc_id'   => null,
            'fame_tasks'               => [],
        ];
    }
}
