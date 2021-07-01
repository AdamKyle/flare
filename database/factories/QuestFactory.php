<?php

namespace Database\Factories;

use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\NpcTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Quest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'               => null,
            'item_id'            => null,
            'gold_dust_cost'     => null,
            'shard_cost'         => null,
            'gold_cost'          => null,
            'reward_item'        => null,
            'reward_gold_dust'   => null,
            'reward_shards'      => null,
            'reward_gold'        => null,
            'reward_xp'          => null,
            'unlocks_skill'      => null,
            'unlocks_skill_type' => null,
        ];
    }
}
