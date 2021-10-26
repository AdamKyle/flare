<?php

namespace Database\Factories;

use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\NpcTypes;
use App\Game\Skills\Values\SkillTypeValue;
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
            'name'               => 'Test',
            'npc_id'             => null,
            'item_id'            => null,
            'gold_dust_cost'     => 10,
            'shard_cost'         => 10,
            'gold_cost'          => 10,
            'reward_item'        => null,
            'reward_gold_dust'   => 10,
            'reward_shards'      => 10,
            'reward_gold'        => 10,
            'reward_xp'          => 10,
            'unlocks_skill'      => true,
            'unlocks_skill_type' => SkillTypeValue::ALCHEMY,
        ];
    }
}
