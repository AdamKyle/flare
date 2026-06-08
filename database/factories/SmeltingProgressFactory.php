<?php

namespace Database\Factories;

use App\Flare\Models\SmeltingProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmeltingProgressFactory extends Factory
{
    protected $model = SmeltingProgress::class;

    public function definition(): array
    {
        return [
            'character_id' => 0,
            'kingdom_id' => 0,
            'started_at' => now(),
            'completed_at' => now()->addHours(1),
            'amount_to_smelt' => 100,
        ];
    }
}
