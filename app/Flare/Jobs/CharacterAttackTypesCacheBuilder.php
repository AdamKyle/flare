<?php

namespace App\Flare\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use App\Flare\Models\Character;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Traits\UpdateMarketBoard;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterAttackTypesCacheBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    /**
     * @var User $user
     */
    public $character;

    public $alertStatsUpdated;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character, bool $alertStatsUpdated = false) {
        $this->character         = $character;
        $this->alertStatsUpdated = $alertStatsUpdated;
    }

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     * @return void
     * @throws \Exception
     */
    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes, Manager $manager, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer) {

        $buildCharacterAttackTypes->buildCache($this->character);

        event(new UpdateTopBarEvent($this->character));

        $this->updateCharacterStats($this->character, $manager, $characterSheetBaseInfoTransformer);

        if ($this->alertStatsUpdated) {
            event(new ExplorationLogUpdate($this->character->user, 'Character stats have been updated.', false, true));
        }
    }

    /**
     * Update the character stats.
     *
     * @param Character $character
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     * @return void
     */
    protected function updateCharacterStats(Character $character, Manager $manager, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer) {
        $characterData = new Item($character, $characterSheetBaseInfoTransformer);
        $characterData = $manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }
}
