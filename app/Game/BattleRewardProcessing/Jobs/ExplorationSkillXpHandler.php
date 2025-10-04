<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Game\Skills\Services\SkillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExplorationSkillXpHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId, private readonly int $xp) {}

    /**
     * Handle the job
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
     */
    private function processSkillXpReward(Character $character, SkillService $skillService): void
    {

        $skillService->setSkillInTraining($character)->giveXpToTrainingSkill($character, $this->xp);
    }
}
