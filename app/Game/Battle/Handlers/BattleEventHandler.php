<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Faction;
use App\Flare\Values\FactionType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Values\FactionLevel;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Item as ItemModel;
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

    private $randomAffixGenerator;

    public function __construct(
        Manager $manager,
        CharacterAttackTransformer $characterAttackTransformer,
        RandomAffixGenerator $randomAffixGenerator,
    ) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->randomAffixGenerator       = $randomAffixGenerator;
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
        $mapId   = $monster->gameMap->id;
        $mapName = $monster->gameMap->name;

        $faction = $character->factions()->where('game_map_id', $mapId)->first();

        $faction->current_points += FactionLevel::gatPointsPerLevel($faction->current_level);

        if ($faction->current_points > $faction->points_needed) {
            $faction->current_points = $faction->points_needed;
        }

        if ($faction->current_points === $faction->points_needed && !FactionLevel::isMaxLevel($faction->current_level, $faction->current_points)) {

            event(new ServerMessageEvent($character->user, $mapName . ' faction has gained a new level!'));

            $faction = $this->updateFaction($faction);

            $this->rewardPlayer($character, $faction);

            event(new ServerMessageEvent($character->user, 'Achieved title: ' . FactionType::getTitle($faction->level) . ' of ' . $mapName));

            return;

        } else if (FactionLevel::isMaxLevel($faction->current_level, $faction->current_points)) {
            event(new ServerMessageEvent($character->user, $mapName . ' faction has become maxed out!'));
            event(new GlobalMessageEvent($character->name . 'Has maxed out the faction for: ' . $mapName . ' They are considered legendary among the people of this land.'));

            $this->rewardPlayer($character, $faction);

            return;
        }

        $faction->save();
    }

    protected function updateFaction(Faction $faction): Faction {
        $faction->current_points = 0;
        $faction->level         += 1;

        $factions->save();

        $faction = $faction->refresh();

        $faction->points_needed  = FactionLevel::getPointsNeeded($faction->level);
        $fatction->title         = FactionType::getTitle($faction->level);

        $factions->save();

        return $faction->refresh();
    }

    protected function rewardPlayer(Character $character, Faction $faction) {
        $this->giveCharacterGold($character, $faction->level);

        $item = $this->giveCharacterRandomItem($character);

        event(new ServerMessageEvent($character->user, 'Achieved title: ' . $title . ' of ' . $mapName));

        if ($character->isInventoryFull()) {
            event(new ServerMessageEvent($character->user, 'You got no item as your inventory is full. Clear space for the next time!'));
        } else {
            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            event(new CharacterInventoryUpdateBroadCastEvent($character->refresh()->user));

            event(new ServerMessageEvent($character->user, 'Rewarded with (item with randomly generated affix): ' . $item->affix_name));
        }
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

    protected function giveCharacterRandomItem(Character $character) {
        $item = ItemModel::where('cost', '<=', RandomAffixDetails::BASIC)
                         ->whereNull('item_prefix_id')
                         ->whereNull('item_suffix_id')
                         ->inRandomOrder()
                         ->first();


        $randomAffix = $this->randomAffixGenerator
                            ->setCharacter($character)
                            ->setPaidAmount(RandomAffixDetails::BASIC);

        $item->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if (rand(1, 100) > 50) {
            $item->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }

        return $item;
    }
}
