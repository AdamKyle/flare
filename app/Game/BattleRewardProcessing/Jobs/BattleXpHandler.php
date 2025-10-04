<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleXpHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId, private int $monsterId) {}

    /**
     * Handle the job
     *
     * @throws Exception
     */
    public function handle(CharacterRewardService $characterRewardService): void
    {
        $character = Character::find($this->characterId);
        $monster = Monster::find($this->monsterId);

        if (is_null($character)) {
            return;
        }

        if (is_null($monster)) {
            return;
        }

        $this->processXpReward($character, $monster, $characterRewardService);
    }

    /**
     * Process XP rewards
     *
     * - Includes character XP
     * - Includes Skill XP
     *
     * @throws Exception
     */
    private function processXpReward(Character $character, Monster $monster, CharacterRewardService $characterRewardService): void
    {
        $characterRewardService->setCharacter($character)
            ->distributeCharacterXP($monster)
            ->distributeSkillXP($monster);
    }
}
