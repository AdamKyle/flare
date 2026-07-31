<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\TopsMonthlySnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopsMonthlySnapshotFactory extends Factory
{
    protected $model = TopsMonthlySnapshot::class;

    public function definition(): array
    {
        $periodStart = now()->subMonthNoOverflow()->startOfMonth();

        return [
            'board_type' => 'characters',
            'metric_key' => 'progression',
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
            'rank' => 1,
            'character_id' => null,
            'subject_type' => Character::class,
            'subject_id' => null,
            'score_integer' => 1,
            'score_decimal' => null,
            'snapshot_data' => [
                'rank' => 1,
                'character_name' => $this->faker->userName(),
            ],
        ];
    }
}
