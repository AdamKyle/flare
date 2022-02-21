<?php

namespace App\Flare\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\CharacterService;
use Facades\App\Flare\Calculators\XPCalculator;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\Item as ItemModel;

class CharacterRewardService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var CharacterXPService $characterXpService
     */
    private $characterXpService;

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterSheetBaseInfoTransformer  $characterSheetBaseInfoTransformer
     */
    private $characterSheetBaseInfoTransformer;

    /**
     * Constructor
     *
     * @param CharacterXPService $characterXpService
     * @param CharacterService $characterService
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     */
    public function __construct(CharacterXPService $characterXpService, CharacterService $characterService, Manager $manager, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer) {
        $this->characterXpService                = $characterXpService;
        $this->characterService                  = $characterService;
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
     * @param Adventure|null $adventure | null
     * @return void
     * @throws \Exception
     */
    public function distributeGoldAndXp(Monster $monster, Adventure $adventure = null) {
        $this->distributeXP($monster, $adventure);

        if ($this->character->xp >= $this->character->xp_next) {
            $this->handleCharacterLevelUp();
        }

        $this->distributeGold($monster);

        $this->distributeCopperCoins($monster);
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
     * Get the skill in training or null
     *
     * @return mixed
     */
    public function fetchCurrentSkillInTraining() {
        return Skill::where('character_id', $this->character->id)->where('currently_training', true)->first();
    }

    /**
     * Fire the update skill event.
     *
     * @param Skill $skill
     * @param Adventure|null $adventure | nul
     * @param Monster|null $monster
     * @return void
     */
    public function trainSkill(Skill $skill, Adventure $adventure = null, Monster $monster = null) {
        event(new UpdateSkillEvent($skill, $adventure, $monster));
    }

    /**
     * Assigns XP to the character.
     *
     * @param Monster $monster
     * @param Adventure|null $adventure
     * @return void
     */
    protected function distributeXP(Monster $monster, Adventure $adventure = null) {
        $currentSkill = $this->fetchCurrentSkillInTraining();
        $xpReduction  = 0.0;
        $gameMap      = $this->character->map->gameMap;

        if (!is_null($currentSkill)) {
            $xpReduction = $currentSkill->xp_towards;

            $this->trainSkill($currentSkill, $adventure, $monster);
        }

        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level, $xpReduction);
        $xp = $this->characterXpService->determineXPToAward($this->character, $xp);

        if (!is_null($gameMap->xp_bonus)) {
            $xp = ($xp + $xp * $gameMap->xp_bonus);
        }

        dump($this->character->xp, $xp);

        $xp = $this->character->xp + $xp;

        $this->character->update([
            'xp' => $xp
        ]);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle character level up.
     *
     * @return void
     */
    public function handleCharacterLevelUp() {
        $this->characterService->levelUpCharacter($this->character);

        $character = $this->character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->updateCharacterStats($character);

        event(new ServerMessageEvent($character->user, 'level_up'));
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
     * @throws \Exception
     */
    protected function distributeGold(Monster $monster) {
        $newGold       = $this->character->gold + $monster->gold;
        $maxCurrencies = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);


        if (!$maxCurrencies->canNotGiveCurrency()) {
            $this->character->update(['gold' => $newGold]);
        }
    }

    /**
     * Give copper coins only to those that have the quest item and are on purgatory.
     *
     * @param Monster $monster
     * @return void
     * @throws \Exception
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
                $newCoins      = $this->character->copper_coins + rand(5, 20);
                $maxCurrencies = new MaxCurrenciesValue($newCoins, MaxCurrenciesValue::COPPER);

                if (!$maxCurrencies->canNotGiveCurrency()) {
                    $this->character->update(['copper_coins' => $newCoins]);
                }
            }
        }
    }
}
