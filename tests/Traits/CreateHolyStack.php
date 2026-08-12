<?php

namespace Tests\Traits;

use App\Flare\Models\HolyStack;

trait CreateHolyStack
{
    public function createHolyStack(array $options = []): HolyStack
    {
        return HolyStack::create(array_merge([
            'devouring_darkness_bonus' => 0.0,
            'stat_increase_bonus' => 0.0,
        ], $options));
    }

    public function createHolyStacks(int $count, array $options = []): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createHolyStack($options);
        }
    }
}
