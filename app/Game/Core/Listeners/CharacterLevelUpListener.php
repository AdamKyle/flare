<?php

namespace App\Game\Core\Listeners;

use Exception;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterService;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class CharacterLevelUpListener
{
    private CharacterService $characterService;

    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * Constructor
     */
    public function __construct(CharacterService $characterService, BuildCharacterAttackTypes $buildCharacterAttackTypes)
    {
        $this->characterService = $characterService;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * Handle the event.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle(CharacterLevelUpEvent $event)
    {

        if ($event->character->xp >= $event->character->xp_next) {
            $this->characterService->levelUpCharacter($event->character);

            $character = $event->character->refresh();

            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::LEVEL_UP, $character->level);

            if ($event->shouldUpdateCache) {
                $this->buildCharacterAttackTypes->buildCache($character);

                event(new UpdateTopBarEvent($character));
                event(new UpdateCharacterAttackEvent($character));
            }
        } else {
            event(new UpdateTopBarEvent($event->character));
        }
    }
}
