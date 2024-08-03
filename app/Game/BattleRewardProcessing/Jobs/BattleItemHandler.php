<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Core\Services\DropCheckService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleItemHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Character $character;

    private Monster $monster;

    public function __construct(Character $character, Monster $monster)
    {
        $this->character = $character;
        $this->monster = $monster;
    }

    /**
     * @throws Exception
     */
    public function handle(DropCheckService $dropCheckService): void
    {
        $dropCheckService->process($this->character->refresh(), $this->monster);
    }
}
