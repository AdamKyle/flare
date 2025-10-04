<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleWeeklyFightHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId, private int $monsterId) {}

    /**
     * Handle the job
     *
     * @throws Exception
     */
    public function handle(WeeklyBattleService $weeklyBattleService): void
    {
        $character = Character::find($this->characterId);
        $monster = Monster::find($this->monsterId);

        if (is_null($character) || is_null($monster)) {
            return;
        }

        $this->processWeeklyFight($character, $monster, $weeklyBattleService);
    }

    /**
     * Process dealing with weekly fights
     *
     * - These are special locations with specific monsters that only be fought once per week.
     *   - Handles rewards and updating the weekly fight details including marking the creature as defeated for the week.
     *
     * @throws Exception
     */
    private function processWeeklyFight(Character $character, Monster $monster, WeeklyBattleService $weeklyBattleService): void
    {
        $weeklyBattleService->handleMonsterDeath($character, $monster);
    }
}
