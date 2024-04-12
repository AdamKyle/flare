<?php

namespace App\Game\Core\Listeners;

use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterService;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;


class CharacterLevelUpListener {

    /**
     * @var CharacterService $characterService
     */
    private CharacterService $characterService;

    /**
     * @var BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * Constructor
     *
     * @param CharacterService $characterService
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
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
     * @throws Exception
     */
    public function handle(CharacterLevelUpEvent $event) {

        if ($event->character->xp >= $event->character->xp_next) {
            $this->characterService->levelUpCharacter($event->character);

            $character = $event->character->refresh();

            ServerMessageHandler::handleMessage($character->user, 'level_up', $character->level);

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
