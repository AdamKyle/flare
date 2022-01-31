<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Faction;
use App\Flare\Models\ItemAffix;
use App\Game\Battle\Services\BattleRewardProcessing;
use App\Game\Core\Values\FactionType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Values\FactionLevel;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Game\Messages\Events\ServerMessageEvent;
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

    private $battleRewardProcessing;

    public function __construct(
        Manager $manager,
        CharacterAttackTransformer $characterAttackTransformer,
        BattleRewardProcessing $battleRewardProcessing,
        RandomAffixGenerator $randomAffixGenerator,
    ) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->battleRewardProcessing     = $battleRewardProcessing;
        $this->randomAffixGenerator       = $randomAffixGenerator;
    }

    public function processDeadCharacter(Character $character) {
        $character->update(['is_dead' => true]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'You are dead. Please revive your self by clicking revive.'));
        event(new AttackTimeOutEvent($character));
        event(new CharacterIsDeadBroadcastEvent($character->user, true));
        event(new UpdateTopBarEvent($character));

        $characterData = new Item($character, $this->characterAttackTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));
    }

    public function processMonsterDeath(Character $character, int $monsterId) {
        $monster = Monster::find($monsterId);

        $this->battleRewardProcessing->handleMonster($character, $monster);

//        if (!$character->map->gameMap->mapType()->isPurgatory()) {
//            $this->handleFactionPoints($character, $monster);
//        }
//
//        event(new UpdateCharacterEvent($character, $monster));
//
//        event(new UpdateTopBarEvent($character->refresh()));
//        event(new DropsCheckEvent($character, $monster));
//        event(new GoldRushCheckEvent($character, $monster));
//
//        $characterData = new Item($character, $this->characterAttackTransformer);
//
//        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));
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



    protected function updateFaction(Faction $faction): Faction {

        $newLevel = $faction->current_level + 1;

        $faction->update([
            'current_points' => 0,
            'current_level'  => $newLevel,
            'points_needed'  => FactionLevel::getPointsNeeded($newLevel),
            'title'          => FactionType::getTitle($newLevel)
        ]);

        return $faction->refresh();
    }

    protected function rewardPlayer(Character $character, Faction $faction, string $mapName, ?string $title = null) {
        $character = $this->giveCharacterGold($character, $faction->current_level);
        $item      = $this->giveCharacterRandomItem($character);

        event(new ServerMessageEvent($character->user, 'Achieved title: ' . $title . ' of ' . $mapName));

        if ($character->isInventoryFull()) {

            event(new ServerMessageEvent($character->user, 'You got no item as your inventory is full. Clear space for the next time!'));
        } else {

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            $character = $character->refresh();

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            event(new ServerMessageEvent($character->user, 'Rewarded with (item with randomly generated affix(es)): ' . $item->affix_name));
        }
    }

    protected function giveCharacterGold(Character $character, int $factionLevel) {
        $gold = FactionLevel::getGoldReward($factionLevel);

        $characterNewGold = $character->gold + $gold;

        $cannotHave = (new MaxCurrenciesValue($characterNewGold, 0))->canNotGiveCurrency();

        if ($cannotHave) {
            $characterNewGold = MaxCurrenciesValue::MAX_GOLD;

            $character->gold = $characterNewGold;
            $character->save();

            event(new ServerMessageEvent($character->user, 'Received faction gold reward: ' . number_format($gold) . ' gold. You are now gold capped.'));

            return $character->refresh();
        }

        $character->gold += $gold;

        event(new ServerMessageEvent($character->user, 'Received faction gold reward: ' . number_format($gold) . ' gold.'));

        $character->save();

        return $character->refresh();
    }

    protected function giveCharacterRandomItem(Character $character) {
        $item = ItemModel::where('cost', '<=', RandomAffixDetails::BASIC)
                         ->whereNull('item_prefix_id')
                         ->whereNull('item_suffix_id')
                         ->where('cost', '<=', 4000000000)
                         ->inRandomOrder()
                         ->first();


        $randomAffix = $this->randomAffixGenerator
                            ->setCharacter($character)
                            ->setPaidAmount(RandomAffixDetails::BASIC);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }

        return $duplicateItem;
    }
}
