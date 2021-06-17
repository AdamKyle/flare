<?php

namespace App\Flare\Handlers;

use Cache;
use Illuminate\Support\Carbon;
use App\Flare\Models\Character;
use Psr\SimpleCache\InvalidArgumentException;


class CheatingCheck {

    /**
     * Is the character cheating when attacking?
     *
     * @param Character $character
     * @return bool
     */
    public function isCheatingInBattle(Character $character): bool {
        if (Cache::has($character->id . '-attack-time')) {
            $lastAttackedAt = Cache::get($character->id . '-attack-time');

            $value = $this->processLastAttack($character, $lastAttackedAt);

            if ($value) {
                return $value;
            }
        }

        Cache::put($character->id . '-attack-time', now());

        return false;
    }

    /**
     * Clear the cache
     *
     * @param $character
     * @throws InvalidArgumentException
     */
    public function clearCache($character) {
        Cache::delete($character->id . '-attack-time');
        Cache::delete($character->id . '-attack-times');
        Cache::delete($character->id . '-possibly-cheating');
    }

    /**
     * Should we timeout?
     *
     * @return bool
     */
    public function shouldTimeOut() {
        return rand(1, 100) > 75;
    }

    /**
     * Process the last attack to determine if cheating.
     *
     * @param Character $character
     * @param Carbon $lastAttackedAt
     * @return bool
     */
    protected function processLastAttack(Character $character, Carbon $lastAttackedAt) {
        if (Cache::has($character->id . '-attack-times')) {
            $times       = Cache::get($character->id . '-attack-times');
            $battleCount = (int) config('cheating.battle_count');

            if (count($times) === $battleCount) {
                $average = array_sum($times) / count($times);

                $isCheating = $this->isAttackerCheating($character, $average);

                if ($this->anyNumberInRange($times) && $this->shouldTimeOut()) {
                    $this->clearCache($character);

                    return true;
                }

                return $isCheating;

            } else {
                $times[] = $lastAttackedAt->diffInSeconds();

                Cache::put($character->id . '-attack-times', $times);
            }
        } else {
            Cache::put($character->id . '-attack-times', [$lastAttackedAt->diffInSeconds()]);
        }

        if (Cache::has($character->id . '-timeout-value')) {
            $time       = Cache::get($character->id . '-timeout-value');
            $timeDelete = (int) config('cheating.time_out_delete');
            $timeOut    = (int) config('cheating.battle_time_out');

            if ($time->diffInMinutes() > $timeDelete) {
                Cache::delete($character->id . '-timeout-value');
            }

            if ($time->diffInMinutes() === $timeOut) {
                $this->clearCache();

                return true;
            }
        } else {
            Cache::get($character->id . '-timeout-value', now());
        }

        return false;
    }

    /**
     * Are we cheating?
     *
     * @param Character $character
     * @param $average
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function isAttackerCheating(Character $character, $average) {
        if ((($average - 4) <= config('cheating.battle_time')) && (config('cheating.battle_time') <= ($average + 4))) {
            if (Cache::has($character->id . '-possibly-cheating')) {
                $count = (int) Cache::get($character->id . '-possibly-cheating');

                if ($count === config('cheating.possibly_cheating')) {

                    if ($this->shouldTimeOut()) {
                        $this->clearCache($character);

                        return true;
                    }

                    $this->clearCache();

                    return false;
                } else {
                    $count += 1;
                    Cache::put($character->id . '-possibly-cheating', $count);
                }
            } else {
                Cache::put($character->id . '-possibly-cheating', 1);
            }
        } else {
            Cache::delete($character->id . '-attack-time');
            Cache::delete($character->id . '-attack-times');
        }

        return false;
    }

    /**
     * Is any number with in range?
     *
     * @param array $times
     * @return bool
     */
    protected function anyNumberInRange(array $times) {
        $withInRange = false;
        $battleTime  = (int) config('cheating.battle_time');

        foreach ($times as $time) {
            if ((($time - 4) <= $battleTime) && ($battleTime <= ($time + 4))) {
                $withInRange = true;
            }
        }

        return $withInRange;
    }
}
