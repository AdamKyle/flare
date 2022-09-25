<?php

namespace App\Game\Battle\Jobs;

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
     * @var bool $isAutomation
     */
    private bool $isAutomation;

    /**
     * @param int $characterId
     * @param int $monsterId
     * @param bool $isAutomation
     */
    public function __construct(int $characterId, int $monsterId, bool $isAutomation = false) {
        $this->characterId  = $characterId;
        $this->monsterId    = $monsterId;
        $this->isAutomation = $isAutomation;
    }

    /**
     * @param BattleEventHandler $battleEventHandler
     * @return void
     */
    public function handle(BattleEventHandler $battleEventHandler): void {
        $battleEventHandler->processMonsterDeath($this->characterId, $this->monsterId, $this->isAutomation);
    }
}
