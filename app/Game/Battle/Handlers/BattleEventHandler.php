<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Battle\Services\BattleRewardProcessing;

class BattleEventHandler {

    private $manager;

    private $characterAttackTransformer;

    private $battleRewardProcessing;

    public function __construct(
        Manager $manager,
        BuildCharacterAttackTypes $characterAttackTransformer,
        BattleRewardProcessing $battleRewardProcessing,
    ) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->battleRewardProcessing     = $battleRewardProcessing;
    }

    public function processDeadCharacter(Character $character) {
        $character->update(['is_dead' => true]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'You are dead. Please revive your self by clicking revive.'));
        event(new AttackTimeOutEvent($character));
        event(new CharacterIsDeadBroadcastEvent($character->user, true));
        event(new UpdateTopBarEvent($character));
        event(new UpdateCharacterStatus($character));

        $characterData = new Item($character, $this->characterAttackTransformer);
        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    public function processMonsterDeath(Character $character, int $monsterId, bool $isAutomation = false) {
        $monster = Monster::find($monsterId);

        $this->battleRewardProcessing->handleMonster($character, $monster, $isAutomation);
    }

    public function processRevive(Character $character): Character {
        $character->update([
            'is_dead' => false
        ]);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();

        if (!is_null($characterInCelestialFight)) {
            $characterInCelestialFight->update([
                'character_current_health' => $character->getInformation()->buildHealth(),
            ]);
        }

        event(new CharacterIsDeadBroadcastEvent($character->user));
        event(new UpdateTopBarEvent($character));

        $character = $character->refresh();
        broadcast(new UpdateCharacterStatus($character));

        return $character;
    }


}
