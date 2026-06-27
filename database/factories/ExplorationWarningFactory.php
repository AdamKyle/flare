<?php

namespace Database\Factories;

use App\Flare\Models\ExplorationWarning;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExplorationWarningFactory extends Factory
{
    protected $model = ExplorationWarning::class;

    public function definition(): array
    {
        return [
            'character_id' => 0,
            'user_id' => 0,
            'exploration_log_id' => null,
            'type' => 'fight',
            'message' => 'Something went wrong.',
            'dismissed_at' => null,
        ];
    }
}
