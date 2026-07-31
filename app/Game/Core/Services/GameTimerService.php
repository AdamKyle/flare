<?php

namespace App\Game\Core\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class GameTimerService
{
    public function seconds(int|float $seconds): int|float
    {
        if (! $this->shouldCapDevelopmentTimers()) {
            return $seconds;
        }

        $maxSeconds = $this->maxDevelopmentSeconds();

        if ($seconds <= $maxSeconds) {
            return $seconds;
        }

        return $maxSeconds;
    }

    public function minutes(int|float $minutes): int|float
    {
        return $this->seconds($minutes * 60) / 60;
    }

    public function hours(int|float $hours): int|float
    {
        return $this->seconds($hours * 3600) / 3600;
    }

    public function availableAtFromSeconds(int|float $seconds): Carbon
    {
        return now()->addSeconds((int) ceil($this->seconds($seconds)));
    }

    public function availableAtFromMinutes(int|float $minutes): Carbon
    {
        return $this->availableAtFromSeconds($minutes * 60);
    }

    public function availableAtFromHours(int|float $hours): Carbon
    {
        return $this->availableAtFromSeconds($hours * 3600);
    }

    public function availableAt(CarbonInterface $availableAt): CarbonInterface
    {
        if (! $this->shouldCapDevelopmentTimers()) {
            return $availableAt;
        }

        $seconds = now()->diffInSeconds($availableAt, false);

        if ($seconds <= 0) {
            return $availableAt;
        }

        return $this->availableAtFromSeconds($seconds);
    }

    private function shouldCapDevelopmentTimers(): bool
    {
        if (! config('game_timers.development_cap.enabled', true)) {
            return false;
        }

        return in_array(app()->environment(), config('game_timers.development_cap.environments', []), true);
    }

    private function maxDevelopmentSeconds(): int
    {
        return max(1, (int) config('game_timers.development_cap.max_seconds', 60));
    }
}
