<?php

namespace App\Game\Battle\Jobs;

use Log;
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
        Log::info('Should be here to process for character: ' . $this->character->name);
        $battleEventHandler->processMonsterDeath($this->character, $this->monsterId);
    }
}
