<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CharacterAttackTypesCacheBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Character $character;

    public bool $alertStatsUpdated;

    /**
     * Create a new job instance.
     */
    public function __construct(Character $character, bool $alertStatsUpdated = false)
    {
        $this->character = $character;
        $this->alertStatsUpdated = $alertStatsUpdated;
    }

    /**
     * @throws Exception
     */
    public function handle(UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes): void
    {

        $updateCharacterAttackTypes->updateCache($this->character);

        if ($this->alertStatsUpdated) {
            event(new ExplorationLogUpdate($this->character->user->id, 'Character stats have been updated.', false, true));
        }
    }
}
