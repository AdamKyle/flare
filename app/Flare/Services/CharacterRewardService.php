<?php

namespace App\Flare\Services;

use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;
use Facades\App\Flare\Calculators\XPCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class CharacterRewardService {

    use MercenaryBonus;

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
    public function __construct(CharacterXPService $characterXpService,
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
     * Distribute the gold and xp to the character.
     *
     * @param Monster $monster
     * @return void
     * @throws Exception
     */
    public function distributeGoldAndXp(Monster $monster): void {
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

        $this->distributeGold($monster);

        $this->distributeCopperCoins($monster);
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
     * Assigns XP to the character.
     *
     * @param Monster $monster
     * @return void
     * @throws Exception
     */
    protected function distributeXP(Monster $monster) {
        $xpReduction  = 0.0;

        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level, $xpReduction);
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

        $this->skillService->assignXPToTrainingSkill($this->character, $xp);

        if (!$this->characterXpService->canCharacterGainXP($this->character)) {
            return;
        }

        $characterXp = (int) $this->character->xp;

        $xp = $characterXp + $xp;

        $this->character->update([
            'xp' => $xp
        ]);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle character level up.
     *
     * @param int $leftOverXP
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
        $item      = ItemModel::where('effect', ItemEffectsValue::GET_COPPER_COINS)->first();

        if (is_null($item)) {
            return;
        }

        $gameMap = GameMap::find($monster->game_map_id);

        if ($gameMap->mapType()->isPurgatory()) {
            $inventory = Inventory::where('character_id', $this->character->id)->first();
            $slot      = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $item->id)->first();

            if (!is_null($slot)) {
                $coins             = rand(5, 20);
                $purgatoryDungeons = $this->purgatoryDungeons($this->character->map);

                if (!is_null($purgatoryDungeons)) {
                    $coins *= 3;
                }

                $coins = $coins + $coins * $this->getCopperCoinBonus($this->character);

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
}
