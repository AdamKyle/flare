<?php

namespace App\Game\Battle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Core\Services\DropCheckService;

class BattleItemHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $monster;

    public function __construct(Character $character, Monster $monster) {
        $this->character = $character;
        $this->monster   = $monster;
    }

    public function handle(DropCheckService $dropCheckService) {
        $dropCheckService->process($this->character->refresh(), $this->monster);
    }
}
