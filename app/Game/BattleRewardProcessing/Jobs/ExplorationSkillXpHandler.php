<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\Skills\Services\SkillService;

class ExplorationSkillXpHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     * @param integer $xp
     */
    public function __construct(private int $characterId, private readonly int $xp) {}

    /**
     * Handle the job
     *
     * @param SkillService $skillService
     * @return void
     */
    public function handle(SkillService $skillService): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $this->processSkillXpReward($character, $skillService);
    }

    /**
     * Process Skill XP Reward
     *
     * @param Character $character
     * @param SkillService $skillService
     * @return void
     */
    private function processSkillXpReward(Character $character, SkillService $skillService): void
    {

        $skillService->setSkillInTraining($character)->giveXpToTrainingSkill($character, $this->xp);
    }
}
