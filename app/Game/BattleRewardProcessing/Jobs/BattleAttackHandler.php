<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Game\Battle\Handlers\BattleEventHandler;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleAttackHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $characterId;

    private int $monsterId;

    private bool $isRankBattle;

    public function __construct(int $characterId, int $monsterId)
    {
        $this->characterId = $characterId;
        $this->monsterId = $monsterId;
    }

    /**
     * @throws Exception
     */
    public function handle(BattleEventHandler $battleEventHandler): void
    {
        $battleEventHandler->processMonsterDeath($this->characterId, $this->monsterId);
    }
}
