<?php

namespace App\Game\Core\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\CharacterRewardService;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Services\CharacterService;

class CharacterLevelUpListener
{

    /**
     * @var CharacterService $characterService
     */
    private $characterService;

    private $buildCharacterAttackTypes;

    /**
     * Constructor
     *
     * @param CharacterService $characterService
     * @return void
     */
    public function __construct(CharacterService $characterService, BuildCharacterAttackTypes $buildCharacterAttackTypes) {
        $this->characterService          = $characterService;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * Handle the event.
     *
     * @param CharacterLevelUpEvent $event
     * @return void
     */
    public function handle(CharacterLevelUpEvent $event)
    {
        if ($event->character->xp >= $event->character->xp_next) {
            $this->characterService->levelUpCharacter($event->character);

            $character = $event->character->refresh();

            $this->buildCharacterAttackTypes->buildCache($character);

            event(new ServerMessageEvent($character->user, 'level_up'));
            event(new UpdateTopBarEvent($character));
            event(new UpdateCharacterAttackEvent($character));

        } else {
            event(new UpdateTopBarEvent($event->character));
        }
    }
}
