<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\Core\Services\GoldRush;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleCurrenciesHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId, private int $monsterId) {}

    /**
     * HAndle the jon
     */
    public function handle(CharacterRewardService $characterRewardService, GoldRush $goldRush): void
    {
        $character = Character::find($this->characterId);
        $monster = Monster::find($this->monsterId);

        if (is_null($character)) {
            return;
        }

        if (is_null($monster)) {
            return;
        }

        $this->handleCurrenciesRewards($character, $monster, $characterRewardService, $goldRush);
    }

    /**
     * Handle currency rewards from the fight.
     *
     * - Also includes potential gold rushes
     *
     * @throws Exception
     */
    private function handleCurrenciesRewards(Character $character, Monster $monster, CharacterRewardService $characterRewardService, GoldRush $goldRush): void
    {
        $characterRewardService->setCharacter($character)
            ->giveCurrencies($monster);

        $character = $character->refresh();

        $goldRush->processPotentialGoldRush($character);
    }
}
