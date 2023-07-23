<?php

namespace App\Game\Core\Listeners;

use App\Flare\Transformers\CharacterCurrenciesTransformer;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Core\Events\UpdateCharacterCurrenciesBroadcastEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;

class UpdateCharacterCurrenciesListener {

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var CharacterCurrenciesTransformer $characterCurrenciesTransformer
     */
    private CharacterCurrenciesTransformer $characterCurrenciesTransformer;

    /**
     * @param Manager $manager
     * @param CharacterTopBarTransformer $characterTopBarTransformer
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     */
    public function __construct(Manager $manager, CharacterCurrenciesTransformer $characterCurrenciesTransformer) {
        $this->manager                           = $manager;
        $this->characterCurrenciesTransformer    = $characterCurrenciesTransformer;
    }

    /**
     * Handle the event.
     *
     * @param UpdateCharacterCurrenciesEvent $event
     * @return void
     */
    public function handle(UpdateCharacterCurrenciesEvent $event): void {
        $characterCurrencies = new Item($event->character, $this->characterCurrenciesTransformer);
        $characterCurrencies = $this->manager->createData($characterCurrencies)->toArray();

        broadcast(new UpdateCharacterCurrenciesBroadcastEvent($characterCurrencies, $event->character->user));
    }
}
