<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExplorationFactionPointHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $characterId
     * @param int $totalFactionPointsToReward
     */
    public function __construct(
        private readonly int $characterId,
        private readonly int $totalFactionPointsToReward
    ) {}

    /**
     * @param FactionHandler $factionHandler
     * @return void
     * @throws Throwable
     */
    public function handle(FactionHandler $factionHandler): void
    {
        if ($this->totalFactionPointsToReward <= 0) {
            return;
        }

        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $factionHandler->awardFactionPointsFromBatch($character, $this->totalFactionPointsToReward);
    }
}
