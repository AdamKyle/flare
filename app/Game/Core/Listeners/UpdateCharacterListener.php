<?php

namespace App\Game\Core\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Services\CharacterRewardService;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Services\CharacterService;

class UpdateCharacterListener
{

    /**
     * @var CharacterService $characterService
     */
    private $characterService;

    /**
     * Constructor
     *
     * @param CharacterService $characterService
     * @return void
     */
    public function __construct(CharacterService $characterService) {
        $this->characterService = $characterService;
    }

    /**
     * Handle the event.
     *
     * @param UpdateCharacterEvent $event
     * @return void
     */
    public function handle(UpdateCharacterEvent $event)
    {
        $characterRewardService = resolve(CharacterRewardService::class, [
            'character' => $event->character,
        ]);

        $characterRewardService->distributeGoldAndXp($event->monster, $event->adventure);

        $character = $characterRewardService->getCharacter();

        event(new CharacterLevelUpEvent($character));
    }
}
