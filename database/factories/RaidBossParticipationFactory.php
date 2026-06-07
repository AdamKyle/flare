<?php

namespace Database\Factories;

use App\Flare\Models\RaidBossParticipation;
use Illuminate\Database\Eloquent\Factories\Factory;

class RaidBossParticipationFactory extends Factory
{
    protected $model = RaidBossParticipation::class;

    public function definition(): array
    {
        return [
            'character_id' => 0,
            'raid_id' => 0,
            'attacks_left' => 5,
            'damage_dealt' => 0,
            'killed_boss' => false,
        ];
    }
}
