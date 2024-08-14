<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;
use Exception;
use Facades\App\Flare\Calculators\XPCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterRewardService
{
    private Character $character;

    private CharacterService $characterService;

    private SkillService $skillService;

    private CharacterXPService $characterXpService;

    private Manager $manager;

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    /**
     * Constructor
     */
    public function __construct(
        CharacterXPService $characterXpService,
        CharacterService $characterService,
        SkillService $skillService,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
    ) {
        $this->characterXpService = $characterXpService;
        $this->characterService = $characterService;
        $this->skillService = $skillService;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
        $this->manager = $manager;
    }

    /**
     * Set the character.
     *
     * @return $this
     */
    public function setCharacter(Character $character): CharacterRewardService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @throws Exception
     */
    public function distributeCharacterXP(Monster $monster): CharacterRewardService
    {
        $this->distributeXP($monster);

        if ($this->character->xp >= $this->character->xp_next) {
            $leftOverXP = $this->character->xp - $this->character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleLevelUps($leftOverXP);
            }

            if ($leftOverXP <= 0) {
                $this->handleCharacterLevelUp(0);
            }
        }

        if (! $this->character->isLoggedIn()) {
            event(new UpdateTopBarEvent($this->character->refresh()));
        }

        return $this;
    }

    /**
     * Distribute Skill Xp
     *
     * @throws Exception
     */
    public function distributeSkillXP(Monster $monster): CharacterRewardService
    {
        $this->skillService->assignXPToTrainingSkill($this->character, $monster->xp);

        return $this;
    }

    /**
     * Give currencies.
     *
     * @throws Exception
     */
    public function giveCurrencies(Monster $monster): CharacterRewardService
    {
        $this->distributeGold($monster);

        $this->distributeCopperCoins($monster);

        $this->currencyEventReward($monster);

        if (! $this->character->is_auto_battling && $this->character->isLoggedIn()) {
            event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
        }

        return $this;
    }

    /**
     * Handles Currency Event Rewards when the event is running.
     */
    public function currencyEventReward(Monster $monster): CharacterRewardService
    {
        $event = ScheduledEvent::where('type', EventType::WEEKLY_CURRENCY_DROPS)->where('currently_running', true)->first();

        if (! is_null($event) && ! $monster->is_celestial_entity) {

            $canHaveCopperCoins = $this->character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::GET_COPPER_COINS;
            })->isNotEmpty();

            $shards = rand(1, 500);

            $goldDust = rand(1, 500);

            $characterShards = $this->character->shards + $shards;
            $characterGoldDust = $this->character->gold_dust + $goldDust;

            if ($canHaveCopperCoins) {
                $copperCoins = rand(1, 150);

                $characterCopperCoins = $this->character->copper_coins + $copperCoins;
            } else {
                $characterCopperCoins = $this->character->copper_coins;
            }

            if ($characterShards > MaxCurrenciesValue::MAX_SHARDS) {
                $characterShards = MaxCurrenciesValue::MAX_SHARDS;
            }

            if ($characterCopperCoins > MaxCurrenciesValue::MAX_COPPER) {
                $characterCopperCoins = MaxCurrenciesValue::MAX_COPPER;
            }

            if ($characterGoldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
                $characterGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            $this->character->update([
                'shards' => $characterShards,
                'copper_coins' => $characterCopperCoins,
                'gold_dust' => $characterGoldDust,
            ]);

            $this->character = $this->character->refresh();

            if (! $this->character->is_auto_battling) {
                event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
            }
        }

        return $this;
    }

    /**
     * Handle instances where we could have multiple level ups.
     */
    protected function handleLevelUps(int $leftOverXP, bool $shouldBuildCache = false): void
    {

        $this->handleCharacterLevelUp($leftOverXP, $shouldBuildCache);

        if ($leftOverXP >= $this->character->xp_next) {
            $leftOverXP = $this->character->xp - $this->character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleLevelUps($leftOverXP, false);
            }

            if ($leftOverXP <= 0) {
                $this->handleLevelUps(0, true);
            }
        }

        if ($leftOverXP < $this->character->xp_next) {
            $this->character->update([
                'xp' => $leftOverXP,
            ]);

            $this->character = $this->character->refresh();
        }
    }

    /**
     * Get the refreshed Character
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Handle character level up.
     */
    public function handleCharacterLevelUp(int $leftOverXP, bool $shouldBuildCache = false): void
    {
        $this->characterService->levelUpCharacter($this->character, $leftOverXP);
        $character = $this->character->refresh();

        if ($shouldBuildCache || $leftOverXP < $character->xp_next) {
            CharacterAttackTypesCacheBuilder::dispatch($character);
            $this->updateCharacterStats($character);
        }

        ServerMessageHandler::handleMessage($character->user, 'level_up', $character->level);
    }

    /**
     * Assigns XP to the character.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function distributeXP(Monster $monster)
    {

        $addBonus = true;

        if (! $this->characterXpService->canCharacterGainXP($this->character)) {
            return;
        }

        // Reduce The XP from the monster if needed.
        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level);

        if ($this->character->level >= $monster->max_level && $this->character->user->show_monster_to_low_level_message) {
            ServerMessageHandler::sendBasicMessage($this->character->user, $monster->name.' has a max level of: '.number_format($monster->max_level).'. You are only getting 1/3rd of: '.number_format($monster->xp).' XP before all bonuses. Move down the list child.');

            $addBonus = false;
        }

        // Get XP based on the skill in trainings training sacrificial amount, ie, give me back 85% of this xp.
        $xp = $this->skillService->getXpWithSkillTrainingReduction($this->character, $xp);

        $event = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first();

        if (is_null($event)) {
            $addBonus = false;
        }

        if ($addBonus) {
            if ($this->character->times_reincarnated > 0) {
                $xp += 1000;
            } else if ($this->character->level > 1000 && $this->character->level <= 5000) {
                $xp += 500;
            } else {
                $xp += 250;
            }
        }

        if ($xp === 0) {
            return;
        }

        $xp = $this->getXpWithBonuses($xp);

        $characterXp = (int) $this->character->xp;

        $xp = $characterXp + $xp;

        $this->character->update([
            'xp' => $xp,
        ]);

        $this->character = $this->character->refresh();
    }

    /**
     * Update the character stats.
     *
     * @return void
     */
    protected function updateCharacterStats(Character $character)
    {
        $characterData = new Item($character, $this->characterSheetBaseInfoTransformer);
        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    /**
     * Gives gold to the player.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function distributeGold(Monster $monster)
    {
        $newGold = $this->character->gold + $monster->gold;

        if ($newGold >= MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $this->character->update([
            'gold' => $newGold,
        ]);
    }

    /**
     * Give copper coins only to those that have the quest item and are on purgatory.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function distributeCopperCoins(Monster $monster)
    {
        $copperCoinsItem = ItemModel::where('effect', ItemEffectsValue::GET_COPPER_COINS)->first();
        $mercenarySlotBonusItem = ItemModel::where('effect', ItemEffectsValue::MERCENARY_SLOT_BONUS)->first();

        if (is_null($copperCoinsItem)) {
            return;
        }

        $gameMap = GameMap::find($monster->game_map_id);

        if ($gameMap->mapType()->isPurgatory()) {
            $inventory = Inventory::where('character_id', $this->character->id)->first();
            $copperCoinSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $copperCoinsItem->id)->first();
            $mercenaryQuestSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $mercenarySlotBonusItem->id)->first();

            if (! is_null($copperCoinSlot)) {
                $coins = rand(5, 20);
                $purgatoryDungeons = $this->purgatoryDungeons($this->character->map);

                if (! is_null($purgatoryDungeons)) {
                    $coins *= 1.5;
                }

                $mercenarySlotBonus = 0;

                if (! is_null($mercenaryQuestSlot)) {
                    $mercenarySlotBonus = 0.5;
                }

                $coins = $coins + $coins * $mercenarySlotBonus;

                $newCoins = $this->character->copper_coins + $coins;
                $maxCurrencies = new MaxCurrenciesValue($newCoins, MaxCurrenciesValue::COPPER);

                if (! $maxCurrencies->canNotGiveCurrency()) {
                    $this->character->update(['copper_coins' => $newCoins]);
                } else {
                    $this->character->update(['copper_coins' => MaxCurrenciesValue::MAX_COPPER]);
                }
            }
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     */
    protected function purgatoryDungeons(Map $map): ?Location
    {
        return Location::whereNotNull('enemy_strength_type')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS)
            ->first();
    }

    /**
     * Fetch XP with additional bonuses.
     *
     * - Applies Guide Quest XP (+10 while under level 2)
     * - Applies Addional bonuses from items and quest items.
     */
    private function getXpWithBonuses(int $xp): int
    {
        $xp = $this->characterXpService->determineXPToAward($this->character, $xp);

        $guideEnabled = $this->character->user->guide_enabled;
        $hasNoCompletedGuideQuests = $this->character->questsCompleted()
            ->whereNotNull('guide_quest_id')
            ->get()
            ->isEmpty();

        if ($guideEnabled && $hasNoCompletedGuideQuests && $this->character->level < 2) {
            $xp += 10;

            event(new ServerMessageEvent($this->character->user, 'Rewarded an extra 10XP while doing the first guide quest. This bonus will end after you reach level 2.'));
        }

        return $xp;
    }
}
