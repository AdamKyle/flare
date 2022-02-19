<?php

namespace App\Game\Battle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Battle\Handlers\BattleEventHandler;

class BattleAttackHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $characterId;

    private $monsterId;

    private $isAutomation;

    public function __construct(int $characterId, int $monsterId, bool $isAutomation = false) {
        $this->characterId  = $characterId;
        $this->monsterId    = $monsterId;
        $this->isAutomation = $isAutomation;
    }

    public function handle(BattleEventHandler $battleEventHandler) {
        $battleEventHandler->processMonsterDeath($this->characterId, $this->monsterId, $this->isAutomation);
    }
}
