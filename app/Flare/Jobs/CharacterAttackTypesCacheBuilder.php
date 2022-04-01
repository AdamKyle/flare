<?php

namespace App\Flare\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Core\Events\UpdateCharacterAttacks;
use App\Game\Exploration\Events\ExplorationLogUpdate;

class CharacterAttackTypesCacheBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * @var bool $alertStatsUpdated
     */
    public bool $alertStatsUpdated;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param bool $alertStatsUpdated
     */
    public function __construct(Character $character, bool $alertStatsUpdated = false) {
        $this->character         = $character;
        $this->alertStatsUpdated = $alertStatsUpdated;
    }

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     * @return void
     * @throws Exception
     */
    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes) {

        $cache = $buildCharacterAttackTypes->buildCache($this->character);

        $this->updateCharacterStats($this->character, $cache);

        if ($this->alertStatsUpdated) {
            event(new ExplorationLogUpdate($this->character->user, 'Character stats have been updated.', false, true));
        }
    }

    /**
     * Update the character attack stats
     *
     * @param Character $character
     * @param array $attackDataCache
     * @return void
     */
    protected function updateCharacterStats(Character $character, array $attackDataCache) {
        event(new UpdateCharacterAttacks($character->user, $attackDataCache));
    }
}
