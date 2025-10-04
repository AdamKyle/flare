<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExplorationXpHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId, private int $numberOfCreatures, private int $xp) {}

    /**
     * Handle the job
     */
    public function handle(CharacterRewardService $characterRewardService, BattleMessageHandler $battleMessageHandler): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $this->processXpReward($character, $characterRewardService, $battleMessageHandler);
    }

    /**
     * Process XP rewards
     *
     * - Includes character XP
     * - Includes Skill XP
     *
     * @param  Monster  $monster
     */
    private function processXpReward(Character $character, CharacterRewardService $characterRewardService, BattleMessageHandler $battleMessageHandler): void
    {
        $battleMessageHandler->handleMessageForExplorationXp($character->user, $this->numberOfCreatures, $this->xp);

        $characterRewardService->setCharacter($character)->distributeSpecifiedXp($this->xp);
    }
}
