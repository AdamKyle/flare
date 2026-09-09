<?php

namespace App\Game\Core\Listeners;

use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Services\CharacterService;
use App\Game\Messages\Types\CharacterMessageTypes;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class CharacterLevelUpListener
{
    private CharacterService $characterService;

    public function __construct(CharacterService $characterService)
    {
        $this->characterService = $characterService;
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
                CharacterAttackTypesCacheBuilder::dispatch($character->refresh());

                event(new UpdateCharacterBaseDetailsEvent($character));
                event(new UpdateCharacterAttackEvent($character));
            }
        } else {
            event(new UpdateCharacterBaseDetailsEvent($event->character));
        }
    }
}
