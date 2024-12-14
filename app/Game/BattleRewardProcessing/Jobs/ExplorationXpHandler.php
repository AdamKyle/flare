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
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;

class ExplorationXpHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     * @param integer $numberOfCreatures
     * @param integer $xp
     */
    public function __construct(private int $characterId, private int $numberOfCreatures, private int $xp) {}

    /**
     * Handle the job
     *
     * @param CharacterRewardService $characterRewardService
     * @param BattleMessageHandler $battleMessageHandler
     * @return void
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
     * @param Character $character
     * @param Monster $monster
     * @param CharacterRewardService $characterRewardService
     * @return void
     */
    private function processXpReward(Character $character, CharacterRewardService $characterRewardService, BattleMessageHandler $battleMessageHandler): void
    {
        $battleMessageHandler->handleMessageForExplorationXp($character->user, $this->numberOfCreatures, $this->xp);

        $characterRewardService->setCharacter($character)->distributeSpecifiedXp($this->xp);
    }
}
