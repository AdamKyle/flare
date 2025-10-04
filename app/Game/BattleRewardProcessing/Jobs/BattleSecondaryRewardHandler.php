<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleSecondaryRewardHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId) {}

    /**
     * Handle the job
     */
    public function handle(SecondaryRewardService $secondaryRewardService): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $this->processSecondaryRewards($character, $secondaryRewardService);
    }

    /**
     * Process secondary rewards
     */
    private function processSecondaryRewards(Character $character, SecondaryRewardService $secondaryRewardService): void
    {
        $secondaryRewardService->handleSecondaryRewards($character);
    }
}
