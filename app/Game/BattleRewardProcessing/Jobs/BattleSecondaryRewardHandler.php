<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;

class BattleSecondaryRewardHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     */
    public function __construct(private int $characterId) {}

    /**
     * Handle the job
     *
     * @param SecondaryRewardService $secondaryRewardService
     * @return void
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
     *
     * @param Character $character
     * @param SecondaryRewardService $secondaryRewardService
     * @return void
     */
    private function processSecondaryRewards(Character $character, SecondaryRewardService $secondaryRewardService): void
    {
        $secondaryRewardService->handleSecondaryRewards($character);
    }
}
