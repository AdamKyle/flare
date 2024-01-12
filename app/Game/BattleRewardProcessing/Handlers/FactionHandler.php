<?php

namespace App\Game\BattleRewardProcessing\Handlers;


use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Values\FactionLevel;
use App\Game\Core\Values\FactionType;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class FactionHandler {

    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @var GuideQuestService $guideQuestService
     */
    private GuideQuestService $guideQuestService;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param GuideQuestService $guideQuestService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, GuideQuestService $guideQuestService) {
        $this->randomAffixGenerator = $randomAffixGenerator;
        $this->guideQuestService    = $guideQuestService;
    }

    /**
     * Handle faction points.
     *
     * @param Character $character
     * @param Monster $monster
     * @return void
     */
    public function handleFaction(Character $character, Monster $monster): void {
        $this->handleFactionPoints($character, $monster, $this->guideQuestService);
    }

    /**
     * Handle faction points for the character.
     *
     * @param Character $character
     * @param Monster $monster
     * @param GuideQuestService $guideQuestService
     * @return void
     */
    protected function handleFactionPoints(Character $character, Monster $monster, GuideQuestService $guideQuestService): void {

        if ($character->currentAutomations->isNotEmpty()) {
            return;
        }

        $map     = GameMap::find($monster->game_map_id);
        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $map->id)->first();

        if (is_null($faction)) {
            return;
        }

        if ($faction->maxed) {
            return;
        }

        if ($this->playerHasQuestItem($character) && $faction->current_level >= 1) {
            $faction->current_points += 95;
        } else {
            $faction->current_points += FactionLevel::gatPointsPerLevel($faction->current_level);
        }

        if ($faction->current_points > $faction->points_needed) {
            $faction->current_points = $faction->points_needed;
        }

        if ($faction->current_points === $faction->points_needed && !FactionLevel::isMaxLevel($faction->current_level)) {
            $this->handleFactionLevelUp($character, $faction, $map->name);

            return;
        } else if (FactionLevel::isMaxLevel($faction->current_level) && !$faction->maxed) {
            $this->handleFactionMaxedOut($character, $faction, $map->name);

            return;
        }

        if ($character->user->guide_enabled) {
            $guideQuest = $guideQuestService->fetchQuestForCharacter($character);

            if (is_null($guideQuest)) {
                $faction->save();

                return;
            }

            $guideQuest = $guideQuest['quest'];

            if (!is_null($guideQuest)) {
                if (!is_null($guideQuest->faction_points_per_kill) && !is_null($guideQuest->required_faction_level)) {
                    if ($faction->game_map_id === $guideQuest->required_faction_id && $guideQuest->required_faction_level !== $faction->current_level) {
                        $faction->current_points += $guideQuest->faction_points_per_kill;

                        event(new ServerMessageEvent($character->user, 'You gained additional ' . $guideQuest->faction_points_per_kill . ' faction points for the current guide quest. This will end once you reach the faction level requirements.'));
                    }
                }
            }
        }

        $faction->save();
    }

    /**
     * Handle giving custom faction points.
     *
     * @param Character $character
     * @param int $amount
     * @return void
     */
    public function handleCustomFactionAmount(Character $character, int $amount): void {
        $map     = Map::where('character_id', $character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);
        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        if (is_null($faction)) {
            return;
        }

        if ($faction->maxed) {
            return;
        }

        if ($this->playerHasQuestItem($character) && $faction->current_level >= 1) {
            $amount *= 10;
        }

        $newAmount = $faction->current_points + $amount;

        $faction->update(['current_points' => $newAmount]);

        $faction = $faction->refresh();

        if ($faction->current_points >= $faction->points_needed && !FactionLevel::isMaxLevel($faction->current_level)) {

            $this->handleFactionLevelUp($character, $faction, $gameMap->name);
        }
    }

    /**
     * Handle faction level up.
     *
     * @param Character $character
     * @param Faction $faction
     * @param string $mapName
     * @return void
     */
    protected function handleFactionLevelUp(Character $character, Faction $faction, string $mapName): void {
        event(new ServerMessageEvent($character->user, $mapName . ' faction has gained a new level!'));

        $faction   = $this->updateFaction($faction);
        $character = $character->refresh();

        $this->rewardPlayer($character, $faction, $mapName, FactionType::getTitle($faction->current_level));

        if (FactionLevel::isMaxLevel($faction->current_level)) {
            $this->handleFactionMaxedOut($character, $faction, $mapName);
        }
    }

    /**
     * Handle when the faction maxes out.
     *
     * @param Character $character
     * @param Faction $faction
     * @param string $mapName
     * @return void
     */
    protected function handleFactionMaxedOut(Character $character, Faction $faction, string $mapName): void {
        event(new ServerMessageEvent($character->user, $mapName . ' faction has become maxed out!'));
        event(new GlobalMessageEvent($character->name . ' Has maxed out the faction for: ' . $mapName . ' They are considered legendary among the people of this land.'));

        $faction->update([
            'maxed' => true,
        ]);
    }

    /**
     * Update the faction.
     *
     * @param Faction $faction
     * @return Faction
     */
    protected function updateFaction(Faction $faction): Faction {

        $newLevel = $faction->current_level + 1;

        $pointsNeeded = FactionLevel::getPointsNeeded($newLevel);

        $faction->update([
            'current_points' => 0,
            'current_level'  => $newLevel,
            'points_needed'  => $pointsNeeded,
            'title'          => FactionType::getTitle($newLevel)
        ]);

        return $faction->refresh();
    }

    /**
     * Give the player a new random unique.
     *
     * - Only gives 10 Billion Valuation items.
     *
     * @param Character $character
     * @param Faction $faction
     * @param string $mapName
     * @param string|null $title
     * @return void
     */
    protected function rewardPlayer(Character $character, Faction $faction, string $mapName, ?string $title = null): void {
        $character = $this->giveCharacterGold($character, $faction->current_level);
        $item      = $this->giveCharacterRandomItem($character);

        event(new ServerMessageEvent($character->user, 'Achieved title: ' . $title . ' of ' . $mapName));

        if ($character->isInventoryFull()) {

            event(new ServerMessageEvent($character->user, 'You got no item as your inventory is full. Clear space for next time!'));
        } else {

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            event(new ServerMessageEvent($character->user, 'Rewarded with (item with randomly generated affix(es)): ' . $item->affix_name, $slot->id));
        }
    }

    /**
     * Give the character gold.
     *
     * @param Character $character
     * @param int $factionLevel
     * @return Character
     * @throws Exception
     */
    protected function giveCharacterGold(Character $character, int $factionLevel): Character {
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

    /**
     * Find a random item to attach the uniques to.
     *
     * @param Character $character
     * @return Item
     * @throws Exception
     */
    protected function giveCharacterRandomItem(Character $character): Item {
        $item = Item::where('cost', '<=', RandomAffixDetails::BASIC)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('specialty_type')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->whereDoesntHave('appliedHolyStacks')
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

    /**
     * See if the player has a quest item for additional points.
     *
     * @param Character $character
     * @return bool
     */
    public function playerHasQuestItem(Character $character): bool {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $item      = Item::where('effect', ItemEffectsValue::FACTION_POINTS)->first();

        if (is_null($item)) {
            return false;
        }

        return !is_null(InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $item->id)->first());
    }
}
