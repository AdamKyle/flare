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
            'gold_dust_cost'     => 100,
            'shard_cost'         => 100,
            'gold_cost'          => 100,
            'reward_item'        => null,
            'reward_gold_dust'   => 100,
            'reward_shards'      => 100,
            'reward_gold'        => 100,
            'reward_xp'          => 100,
            'unlocks_skill'      => true,
            'unlocks_skill_type' => SkillTypeValue::ALCHEMY,
        ];
    }
}
