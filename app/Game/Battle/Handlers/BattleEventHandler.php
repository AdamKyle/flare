<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Values\FactionType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Values\FactionLevel;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Maps\Events\UpdateActionsBroadcast;
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

        $this->handleFactionPoints($character, $monster);

        $character = $character->refresh();

        event(new UpdateCharacterEvent($character, $monster));
        event(new DropsCheckEvent($character, $monster));
        event(new GoldRushCheckEvent($character, $monster));

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

        $character = $character->refresh();
        $mapId     = $character->map->gameMap->id;
        $user      = $character->user;

        $monsters  = Cache::get('monsters')[GameMap::find($mapId)->name];

        $characterData = new Item($character, $this->characterAttackTransformer);
        $characterData = $this->manager->createData($characterData)->toArray();

        broadcast(new UpdateActionsBroadcast($characterData, $monsters, $user));

        return $character;
    }

    protected function handleFactionPoints(Character $character, Monster $monster) {
        $mapId = $monster->gameMap->id;
        $mapName = $monster->gameMap->name;

        $faction = $character->factions()->where('game_map_id', $mapId)->first();

        $faction->current_points += FactionLevel::gatPointsPerLevel($faction->current_level);

        if ($faction->current_points > $faction->points_needed) {
            $faction->current_points = $faction->points_needed;
        }

        if ($faction->current_points === $faction->points_needed && !FactionLevel::isMaxLevel($faction->current_level, $faction->current_points)) {

            event(new ServerMessageEvent($character->user, $mapName . ' faction has gained a new level!'));

            $title = FactionType::getTitle($faction->level);

            $faction->current_points = 0;
            $faction->level         += 1;
            $faction->points_needed  = FactionLevel::getPointsNeeded($faction->level);
            $fatction->title         = $title;

            $this->giveCharacterGold($character, $faction->refresh()->level);

            event(new ServerMessageEvent($character->user, 'Achieved title: ' . $title . ' of ' . $mapName));
        }

        $faction->save();
    }

    protected function giveCharacterGold(Character $character, int $factionLevel) {
        $gold = FactionLevel::getGoldReward($factionLevel);

        $characterNewGold = $character->gold + $gold;

        $cannotHave = (new MaxCurrenciesValue($characterNewGold, 0))->canNotGiveCurrency();

        if ($cannotHave) {
            event(new ServerMessageEvent($character->user, 'Failed to reward the gold as you are, or are too close to gold cap to receive: ' . number_format($gold) . ' gold.'));

            return $character;
        }

        $character->gold += $gold;

        event(new ServerMessageEvent($character->user, 'Received Faction Gold Reward: ' . number_format($gold) . ' gold.'));

        $character->save();

        return $character;
    }
}
