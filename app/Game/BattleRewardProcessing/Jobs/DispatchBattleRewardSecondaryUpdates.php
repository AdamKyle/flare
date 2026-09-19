<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Game\BattleRewardProcessing\Services\BattleRewardSecondaryUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchBattleRewardSecondaryUpdates implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $uniqueFor = 180;

    /**
     * @param int $characterId
     */
    public function __construct(private readonly int $characterId) {}

    /**
     * Dispatch the Character's non-authoritative Tops/profile and compatibility currency updates.
     *
     * @param BattleRewardSecondaryUpdateService $battleRewardSecondaryUpdateService
     * @return void
     */
    public function handle(BattleRewardSecondaryUpdateService $battleRewardSecondaryUpdateService): void
    {
        $battleRewardSecondaryUpdateService->flush($this->characterId);
    }

    /**
     * Return the Character-scoped uniqueness identity for queued secondary reward updates.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'battle-reward-secondary:'.$this->characterId;
    }
}
