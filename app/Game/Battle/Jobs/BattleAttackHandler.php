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

    private $character;

    private $monsterId;

    public function __construct(Character $character, int $monsterId) {
        $this->character = $character;
        $this->monsterId = $monsterId;
    }

    public function handle(BattleEventHandler $battleEventHandler) {
        $battleEventHandler->processMonsterDeath($this->character, $this->monsterId);
    }
}
