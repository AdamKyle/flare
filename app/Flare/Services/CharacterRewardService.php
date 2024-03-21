<?php

namespace App\Flare\Services;

use Exception;
use App\Flare\Models\Map;
use App\Flare\Models\Event;
use League\Fractal\Manager;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Game\Events\Values\EventType;
use League\Fractal\Resource\Item;
use App\Flare\Values\LocationType;
use App\Flare\Models\InventorySlot;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Skills\Services\SkillService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterService;
use Facades\App\Flare\Calculators\XPCalculator;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;

class CharacterRewardService {

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var CharacterService $characterService
     */
    private CharacterService $characterService;

    /**
     * @var SkillService $skillService
     */
    private SkillService $skillService;

    /**
     * @var CharacterXPService $characterXpService
     */
    private CharacterXPService $characterXpService;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var CharacterSheetBaseInfoTransformer  $characterSheetBaseInfoTransformer
     */
    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    /**
     * Constructor
     *
     * @param CharacterXPService $characterXpService
     * @param CharacterService $characterService
     * @param SkillService $skillService
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     */
    public function __construct(
        CharacterXPService $characterXpService,
        CharacterService $characterService,
        SkillService $skillService,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
    ) {
        $this->characterXpService                = $characterXpService;
        $this->characterService                  = $characterService;
        $this->skillService                      = $skillService;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
        $this->manager                           = $manager;
    }

    /**
     * Set the character.
     *
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): CharacterRewardService {
        $this->character = $character;

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @param Monster $monster
     * @return CharacterRewardService
     * @throws Exception
     */
    public function distributeCharacterXP(Monster $monster): CharacterRewardService {
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

        if (!$this->character->isLoggedIn()) {
            event(new UpdateTopBarEvent($this->character->refresh()));
        }

        return $this;
    }

    /**
     * Distribute Skill Xp
     *
     * @param Monster $monster
     * @return CharacterRewardService
     * @throws Exception
     */
    public function distributeSkillXP(Monster $monster): CharacterRewardService {
        $this->skillService->assignXPToTrainingSkill($this->character, $monster->xp);

        return $this;
    }

    /**
     * Give currencies.
     *
     * @param Monster $monster
     * @return CharacterRewardService
     * @throws Exception
     */
    public function giveCurrencies(Monster $monster): CharacterRewardService {
        $this->distributeGold($monster);

        $this->distributeCopperCoins($monster);

        $this->currencyEventReward($monster);

        if (!$this->character->is_auto_battling && $this->character->isLoggedIn()) {
            event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
        }

        return $this;
    }

    /**
     * Handles Currency Event Rewards when the event is running.
     *
     * @param Monster $monster
     * @return CharacterRewardService
     */
    public function currencyEventReward(Monster $monster): CharacterRewardService {
        $event = Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->first();

        if (!is_null($event) && !$monster->is_celestial_entity) {

            $canHaveCopperCoins = $this->character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::GET_COPPER_COINS;
            })->isNotEmpty();

            $shards = rand(1, 500);
            $shards = $shards + $shards * $this->getShardBonus($this->character);

            $goldDust = rand(1, 500);
            $goldDust = $goldDust + $goldDust * $this->getGoldDustBonus($this->character);

            $characterShards      = $this->character->shards + $shards;
            $characterGoldDust    = $this->character->gold_dust + $goldDust;

            if ($canHaveCopperCoins) {
                $copperCoins = rand(1, 150);
                $copperCoins = $copperCoins + $copperCoins * $this->getCopperCoinBonus($this->character);

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
                'shards'       => $characterShards,
                'copper_coins' => $characterCopperCoins,
                'gold_dust'    => $characterGoldDust
            ]);

            $this->character = $this->character->refresh();

            if (!$this->character->is_auto_battling) {
                event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
            }
        }

        return $this;
    }


    /**
     * Handle instances where we could have multiple level ups.
     *
     * @param int $leftOverXP
     * @return void
     */
    protected function handleLevelUps(int $leftOverXP, bool $shouldBuildCache = false): void {

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
                'xp' => $leftOverXP
            ]);

            $this->character = $this->character->refresh();
        }
    }

    /**
     * Get the refreshed Character
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    /**
     * Handle character level up.
     *
     * @param int $leftOverXP
     * @param bool $shouldBuildCache
     * @return void
     */
    public function handleCharacterLevelUp(int $leftOverXP, bool $shouldBuildCache = false): void {
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
     * @param Monster $monster
     * @return void
     * @throws Exception
     */
    protected function distributeXP(Monster $monster) {

        if (!$this->characterXpService->canCharacterGainXP($this->character)) {
            return;
        }

        // Reduce The XP from the monster if needed.
        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level);

        // Get XP based on the skill in trainings training sacraficial amount, ie, give me back 85% of this xp.
        $xp = $this->skillService->getXpWithSkillTrainingReduction($this->character, $xp);

        if ($xp === 0) {
            return;
        }

        $xp = $this->getXpWithBonuses($xp);

        $characterXp = (int) $this->character->xp;

        $xp = $characterXp + $xp;

        $this->character->update([
            'xp' => $xp
        ]);

        $this->character = $this->character->refresh();
    }

    /**
     * Update the character stats.
     *
     * @param Character $character
     * @return void
     */
    protected function updateCharacterStats(Character $character) {
        $characterData = new Item($character, $this->characterSheetBaseInfoTransformer);
        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    /**
     * Gives gold to the player.
     *
     * @param Monster $monster
     * @return void
     * @throws Exception
     */
    protected function distributeGold(Monster $monster) {
        $newGold       = $this->character->gold + $monster->gold;

        if ($newGold >= MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $this->character->update([
            'gold' => $newGold
        ]);
    }

    /**
     * Give copper coins only to those that have the quest item and are on purgatory.
     *
     * @param Monster $monster
     * @return void
     * @throws Exception
     */
    protected function distributeCopperCoins(Monster $monster) {
        $copperCoinsItem      = ItemModel::where('effect', ItemEffectsValue::GET_COPPER_COINS)->first();
        $mercenarySlotBonusItem  = ItemModel::where('effect', ItemEffectsValue::MERCENARY_SLOT_BONUS)->first();

        if (is_null($copperCoinsItem)) {
            return;
        }

        $gameMap = GameMap::find($monster->game_map_id);

        if ($gameMap->mapType()->isPurgatory()) {
            $inventory = Inventory::where('character_id', $this->character->id)->first();
            $copperCoinSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $copperCoinsItem->id)->first();
            $mercenaryQuestSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $mercenarySlotBonusItem->id)->first();

            if (!is_null($copperCoinSlot)) {
                $coins             = rand(5, 20);
                $purgatoryDungeons = $this->purgatoryDungeons($this->character->map);

                if (!is_null($purgatoryDungeons)) {
                    $coins *= 1.5;
                }

                $mercenarySlotBonus = 0;

                if (!is_null($mercenaryQuestSlot)) {
                    $mercenarySlotBonus = 0.5;
                }

                $coins = $coins + $coins * $mercenarySlotBonus;

                $newCoins          = $this->character->copper_coins + $coins;
                $maxCurrencies     = new MaxCurrenciesValue($newCoins, MaxCurrenciesValue::COPPER);

                if (!$maxCurrencies->canNotGiveCurrency()) {
                    $this->character->update(['copper_coins' => $newCoins]);
                } else {
                    $this->character->update(['copper_coins' => MaxCurrenciesValue::MAX_COPPER]);
                }
            }
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     *
     * @param Map $map
     * @return Location|null
     */
    protected function purgatoryDungeons(Map $map): ?Location {
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
     *
     * @param int $xp
     * @return integer
     */
    private function getXpWithBonuses(int $xp): int {
        $xp = $this->characterXpService->determineXPToAward($this->character, $xp);

        $guideEnabled              = $this->character->user->guide_enabled;
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
