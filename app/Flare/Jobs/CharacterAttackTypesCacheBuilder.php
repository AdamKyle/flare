<?php

namespace App\Flare\Jobs;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Traits\UpdateMarketBoard;
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
     * @param UpdateCharacterAttackTypes $updateCharacterAttackTypes
     * @return void
     * @throws Exception
     */
    public function handle(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {

        $updateCharacterAttackTypes->updateCache($this->character);

        if ($this->alertStatsUpdated) {
            event(new ExplorationLogUpdate($this->character->user->id, 'Character stats have been updated.', false, true));
        }
    }
}
