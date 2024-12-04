<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;

class BattleWeeklyFightHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     * @param integer $monsterId
     */
    public function __construct(private int $characterId, private int $monsterId) {}

    /**
     * Handle the job
     *
     * @param WeeklyBattleService $weeklyBattleService
     * @return void
     */
    public function handle(WeeklyBattleService $weeklyBattleService): void
    {
        $character = Character::find($this->characterId);
        $monster = Monster::find($this->monsterId);

        if (is_null($character)) {
            return;
        }

        if (is_null($monster)) {
            return;
        }
    }

    /**
     * Process dealing with weeklu fights
     *
     * - These are special locations with specific monsters that only be faught once per week.
     *   - Handles rewards and updating the weekly fight details including marking the creature as ddefeated for the week.
     *
     * @param Character $character
     * @param Monster $monster
     * @param WeeklyBattleService $weeklyBattleService
     * @return void
     */
    private function processWeeklyFight(Character $character, Monster $monster, WeeklyBattleService $weeklyBattleService): void
    {
        $weeklyBattleService->handleMonsterDeath($character, $monster);
    }
}
