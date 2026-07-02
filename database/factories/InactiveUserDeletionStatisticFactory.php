<?php

namespace Database\Factories;

use App\Flare\Models\InactiveUserDeletionStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

class InactiveUserDeletionStatisticFactory extends Factory
{
    protected $model = InactiveUserDeletionStatistic::class;

    public function definition()
    {
        return [
            'deleted_count' => 1,
            'tracked_at' => now(),
        ];
    }
}
