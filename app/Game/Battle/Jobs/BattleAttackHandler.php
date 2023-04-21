<?php

namespace App\Game\Battle\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Game\Battle\Handlers\BattleEventHandler;

class  BattleAttackHandler implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int $characterId
     */
    private int $characterId;

    /**
     * @var int $monsterId
     */
    private int $monsterId;

    /**
     * @var bool $isRankBattle
     */
    private bool $isRankBattle;

    /**
     * @param int $characterId
     * @param int $monsterId
     */
    public function __construct(int $characterId, int $monsterId) {
        $this->characterId  = $characterId;
        $this->monsterId    = $monsterId;
    }

    /**
     * @param BattleEventHandler $battleEventHandler
     * @return void
     * @throws Exception
     */
    public function handle(BattleEventHandler $battleEventHandler): void {
        $battleEventHandler->processMonsterDeath($this->characterId, $this->monsterId);
    }
}
