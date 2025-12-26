<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BattleRewardHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $characterId;

    private int $monsterId;

    private array $context;

    public function __construct(int $characterId, int $monsterId, array $context = [])
    {
        $this->characterId = $characterId;
        $this->monsterId = $monsterId;
        $this->context = $context;
    }

    /**
     * Process rewards from the fight.
     *
     * @param BattleRewardService $battleRewardService
     * @return void
     * @throws Throwable
     */
    public function handle(BattleRewardService $battleRewardService): void
    {
        $battleRewardService->setUp($this->characterId, $this->monsterId)->setContext($this->context)->processRewards(true);
    }
}
