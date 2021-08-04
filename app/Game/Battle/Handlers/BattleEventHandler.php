<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Core\Events\UpdateAttackStats;

class BattleEventHandler {

    private $manager;

    private $characterAttackTransformer;

    public function __construct(Manager $manager, CharacterAttackTransformer $characterAttackTransformer) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    public function processDeadCharacter(Character $character) {
        $character->update(['is_dead' => true]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'dead_character'));
        event(new AttackTimeOutEvent($character));
        event(new CharacterIsDeadBroadcastEvent($character->user, true));
        event(new UpdateTopBarEvent($character));

        $characterData = new Item($character, $this->characterAttackTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));
    }

    public function processMonsterDeath(Character $character, int $monsterId) {
        $monster = Monster::find($monsterId);


        event(new UpdateCharacterEvent($character, $monster));

        event(new DropsCheckEvent($character, $monster));
        event(new GoldRushCheckEvent($character, $monster));

        event(new AttackTimeOutEvent($character));

        $characterData = new Item($character, $this->characterAttackTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));
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

        return $character->refresh();
    }
}
