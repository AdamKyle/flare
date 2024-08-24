<?php

namespace Database\Factories;

use App\Flare\Models\Raid;
use App\Flare\Values\ItemSpecialtyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RaidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Raid::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::random(),
            'story' => Str::random(),
            'raid_boss_id' => null,
            'raid_monster_ids' => [],
            'raid_boss_location_id' => null,
            'corrupted_location_ids' => [],
            'item_specialty_reward_type' => ItemSpecialtyType::PIRATE_LORD_LEATHER,
            'artifact_item_id' => null,
        ];
    }
}
