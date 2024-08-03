<?php

namespace App\Game\Core\Listeners;

use App\Flare\Transformers\CharacterCurrenciesTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Core\Events\UpdateCharacterCurrenciesBroadcastEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UpdateCharacterCurrenciesListener
{
    private Manager $manager;

    private CharacterCurrenciesTransformer $characterCurrenciesTransformer;

    /**
     * @param  CharacterTopBarTransformer  $characterTopBarTransformer
     * @param  CharacterSheetBaseInfoTransformer  $characterSheetBaseInfoTransformer
     */
    public function __construct(Manager $manager, CharacterCurrenciesTransformer $characterCurrenciesTransformer)
    {
        $this->manager = $manager;
        $this->characterCurrenciesTransformer = $characterCurrenciesTransformer;
    }

    /**
     * Handle the event.
     */
    public function handle(UpdateCharacterCurrenciesEvent $event): void
    {
        $characterCurrencies = new Item($event->character, $this->characterCurrenciesTransformer);
        $characterCurrencies = $this->manager->createData($characterCurrencies)->toArray();

        broadcast(new UpdateCharacterCurrenciesBroadcastEvent($characterCurrencies, $event->character->user));
    }
}
