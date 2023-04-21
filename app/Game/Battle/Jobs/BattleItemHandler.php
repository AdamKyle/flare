<?php

namespace App\Game\Battle\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Core\Services\DropCheckService;

class BattleItemHandler implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var Monster $monster
     */
    private Monster $monster;

    /**
     * @param Character $character
     * @param Monster $monster
     */
    public function __construct(Character $character, Monster $monster) {
        $this->character = $character;
        $this->monster   = $monster;
    }

    /**
     * @param DropCheckService $dropCheckService
     * @return void
     * @throws Exception
     */
    public function handle(DropCheckService $dropCheckService): void {
        $dropCheckService->process($this->character->refresh(), $this->monster);
    }
}
