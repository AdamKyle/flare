<?php

namespace App\Flare\Services;

use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Skills\Services\SkillService;
use Facades\App\Flare\Calculators\XPCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Collection;

class CharacterXPService
{
    private Character $character;

    /**
     * @param CharacterService $characterService
     * @param SkillService $skillService
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     * @param BattleMessageHandler $battleMessageHandler
     */
    public function __construct(
        private readonly CharacterService $characterService,
        private readonly SkillService $skillService,
        private readonly Manager $manager,
        private readonly CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        private readonly BattleMessageHandler $battleMessageHandler,
    ) {
    }

    /**
     * Set the character.
     *
     * @param Character $character
     * @return CharacterXPService
     */
    public function setCharacter(Character $character): CharacterXPService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @param Monster $monster
     * @return CharacterXPService
     * @throws Exception
     */
    public function distributeCharacterXP(Monster $monster): CharacterXPService
    {
        $this->distributeXP($monster);

        $this->handleLevelUp();

        if (! $this->character->isLoggedIn()) {
            event(new UpdateTopBarEvent($this->character->refresh()));
        }

        return $this;
    }

    /**
     * Distribute a specific amount of XP
     *
     * @param integer $xp
     * @return CharacterXPService
     */
    public function distributeSpecifiedXp(int $xp): CharacterXPService
    {

        $this->character->update([
            'xp' => $this->character->xp + $xp,
        ]);

        $this->character = $this->character->refresh();

        $this->handleLevelUp();

        return $this;
    }

    /**
     * Handle possible level up.
     *
     * Takes into account XP over flow.
     *
     * @return void
     */
    public function handleLevelUp(): void
    {
        if ($this->character->xp >= $this->character->xp_next) {
            $leftOverXP = $this->character->xp - $this->character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleMultipleLevelUps($leftOverXP);
            }

            if ($leftOverXP <= 0) {
                $this->handleCharacterLevelUp(0);
            }
        }
    }

    /**
     * Get the refreshed Character
     *
     * @return Character
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Handle character level up.
     *
     * @param int $leftOverXP
     * @param bool $shouldBuildCache
     * @return void
     */
    public function handleCharacterLevelUp(int $leftOverXP, bool $shouldBuildCache = false): void
    {
        $this->characterService->levelUpCharacter($this->character, $leftOverXP);
        $character = $this->character->refresh();

        if ($shouldBuildCache || $leftOverXP < $character->xp_next) {
            CharacterAttackTypesCacheBuilder::dispatch($character);
            $this->updateCharacterStats($character);
        }

        ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::LEVEL_UP, $character->level);
    }

    /**
     * Fetch the xp for the monster
     *
     * - Can return 0 if we cannot gain xp.
     * - Can return 0 if the xp we would gain is 0.
     * - Takes into account skills in training
     * - Takes into account Xp Bonuses such as items (Alchemy and quest)
     *
     * @param Monster $monster
     * @return integer
     */
    public function fetchXpForMonster(Monster $monster): int
    {
        $addBonus = true;

        if (! $this->canCharacterGainXP($this->character)) {
            return 0;
        }

        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level);

        if ($this->character->level >= $monster->max_level && $this->character->user->show_monster_to_low_level_message) {
            ServerMessageHandler::sendBasicMessage($this->character->user, $monster->name . ' has a max level of: ' . number_format($monster->max_level) . '. You are only getting 1/3rd of: ' . number_format($monster->xp) . ' XP before all bonuses. Move down the list child.');

            $addBonus = false;
        }

        $xp = $this->skillService->setSkillInTraining($this->character)->getCharacterXpWithSkillTrainingReduction($this->character, $xp);

        $event = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first();

        if (is_null($event)) {
            $addBonus = false;
        }

        if ($addBonus) {
            if ($this->character->times_reincarnated > 0) {
                $xp += 500;
            } else if ($this->character->level > 1000 && $this->character->level <= 5000) {
                $xp += 150;
            } else {
                $xp += 75;
            }
        }

        if ($xp === 0) {
            return 0;
        }

        return $this->getXpWithBonuses($xp);
    }

    /**
     * Determine the XP to reward.
     *
     * - Calculate based on two things:
     *   - All quest items that ignore the caps
     *   - All quest items that do no ignore caps
     *   - Add both together to get the XP.
     */
    public function determineXPToAward(Character $character, int $xp): int
    {

        if ($xp === 0) {
            return 0;
        }

        $canContinueLeveling = $this->canContinueLeveling($character);

        $xpBonusQuestSlots = $this->findAllItemsThatGiveXpBonus($character);
        $boonBonus = $character->boons->sum('itemUsed.xp_bonus');
        $map = $character->map->gameMap;
        $mapBonus = ! is_null($map->xp_bonus) ? $map->xp_bonus : 0;

        $xpBonusIgnoreCaps = $this->getTotalXpBonus($xpBonusQuestSlots, true) + $boonBonus + $mapBonus;
        $xpBonusWithCaps = $this->getTotalXpBonus($xpBonusQuestSlots, false);

        if ($canContinueLeveling) {
            return $this->continueLevelingXpWithBonuses($character, $xp, $xpBonusIgnoreCaps, $xpBonusWithCaps);
        }

        return $this->regularLevelingXpWithBonuses($character, $xp, $xpBonusIgnoreCaps, $xpBonusWithCaps);
    }

    /**
     * Can the character gain XP?
     */
    public function canCharacterGainXP(Character $character): bool
    {

        $canContinueLeveling = $this->canContinueLeveling($character);

        if ($canContinueLeveling) {
            $config = MaxLevelConfiguration::first();

            if (is_null($config)) {
                return $character->level !== MaxLevel::MAX_LEVEL;
            }

            return $character->level !== $config->max_level;
        }

        return $character->level !== MaxLevel::MAX_LEVEL;
    }

    /**
     * Is the character halfway to max?
     */
    public function isCharacterHalfWay(int $characterLevel): bool
    {
        $halfWay = MaxLevelConfiguration::first()->half_way;
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;

        return $characterLevel >= $halfWay && $characterLevel < $threeQuarters;
    }

    /**
     * Are we 75% of the way to max?
     */
    public function isCharacterThreeQuarters(int $characterLevel): bool
    {
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;
        $lastLeg = MaxLevelConfiguration::first()->last_leg;

        return $characterLevel >= $threeQuarters && $characterLevel < $lastLeg;
    }

    /**
     * Are we at the last 100 levels?
     */
    public function isCharacterAtLastLeg(int $characterLevel): bool
    {
        $lastLeg = MaxLevelConfiguration::first()->last_leg;
        $maxLevel = MaxLevelConfiguration::first()->max_level;

        return $characterLevel >= $lastLeg && $characterLevel < $maxLevel;
    }

    /**
     * Handle instances where we could have multiple level ups.
     */
    private function handleMultipleLevelUps(int $leftOverXP, bool $shouldBuildCache = false): void
    {

        $this->handleCharacterLevelUp($leftOverXP, $shouldBuildCache);

        if ($leftOverXP >= $this->character->xp_next) {
            $leftOverXP = $this->character->xp - $this->character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleMultipleLevelUps($leftOverXP, false);
            }

            if ($leftOverXP <= 0) {
                $this->handleMultipleLevelUps(0, true);
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
     * Assigns XP to the character.
     *
     * @param Monster $monster
     * @return void
     */
    private function distributeXP(Monster $monster): void
    {

        $xp = $this->fetchXpForMonster($monster);

        $this->character->update([
            'xp' => $this->character->xp + $xp,
        ]);

        $this->character = $this->character->refresh();

        $this->battleMessageHandler->handleXPMessage($this->character->user, $xp, $this->character->xp);
    }

    /**
     * Update the character stats.
     *
     * @param Character $character
     * @return void
     */
    private function updateCharacterStats(Character $character): void
    {
        $characterData = new Item($character, $this->characterSheetBaseInfoTransformer);
        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    /**
     * Fetch XP with additional bonuses.
     *
     * - Applies Guide Quest XP (+10 while under level 2)
     * - Applies Addional bonuses from items and quest items.
     *
     * @param int $xp
     * @return int
     */
    private function getXpWithBonuses(int $xp): int
    {
        $xp = $this->determineXPToAward($this->character, $xp);

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

    /**
     * Get xp when we can continue leveling.
     */
    private function continueLevelingXpWithBonuses(Character $character, int $xp, float $xpBonusIgnoreCaps, float $xpBonusWithCaps): int
    {
        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps === 0.0) {
            return $this->getXP($character, true, $xpBonusIgnoreCaps, $xp);
        }

        if ($xpBonusWithCaps > 0 && $xpBonusIgnoreCaps === 0.0) {
            return $this->getXP($character, false, $xpBonusWithCaps, $xp);
        }

        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps > 0) {
            $xp = $this->getXP($character, true, $xpBonusIgnoreCaps, $xp);

            return $this->getXP($character, false, $xpBonusWithCaps, $xp);
        }

        return $this->getXP($character, false, $xpBonusWithCaps, $xp);
    }

    /**
     * Get Xp when regular leveling.
     */
    private function regularLevelingXpWithBonuses(Character $character, int $xp, float $xpBonusIgnoreCaps, float $xpBonusWithCaps): int
    {
        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps === 0.0) {
            return (new MaxLevel($character->level, $xp))->fetchXP(true, $xpBonusIgnoreCaps);
        }

        if ($xpBonusWithCaps > 0 && $xpBonusIgnoreCaps === 0.0) {
            return (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);
        }

        if ($xpBonusIgnoreCaps > 0 && $xpBonusWithCaps > 0) {
            $xp = (new MaxLevel($character->level, $xp))->fetchXP(true, $xpBonusIgnoreCaps);

            return (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);
        }

        return (new MaxLevel($character->level, $xp))->fetchXP(false, $xpBonusWithCaps);
    }

    /**
     * Get xp.
     *
     * Takes into consideration:
     *
     * - If we can continue leveling
     * - If we should ignore XP caps.
     * - Any additional bonus.
     *
     * All of which is added to the xp.
     */
    private function getXP(Character $character, bool $ignoreCaps, float $xpBonus, int $xp): float
    {
        $config = MaxLevelConfiguration::first();

        if (is_null($config)) {
            return (new MaxLevel($character->level, $xp))->fetchXP($ignoreCaps, $xpBonus);
        }

        if ($this->isCharacterHalfWay($character->level) && ! $ignoreCaps) {
            return ceil($xp * MaxLevel::HALF_PERCENT);
        }

        if ($this->isCharacterThreeQuarters($character->level) && ! $ignoreCaps) {
            return ceil($xp * MaxLevel::THREE_QUARTERS_PERCENT);
        }

        if ($this->isCharacterAtLastLeg($character->level) && ! $ignoreCaps) {
            return ceil($xp * MaxLevel::LAST_LEG_PERCENT);
        }

        return $xp + $xp * $xpBonus;
    }

    /**
     * Find all quest items that give xp bonus.
     */
    private function findAllItemsThatGiveXpBonus(Character $character): Collection
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->join('items', function ($join) {
            $join->on('items.id', 'inventory_slots.item_id')->where('items.type', 'quest')->whereNotNull('items.xp_bonus');
        })->select('inventory_slots.*')->get();
    }

    /**
     * Do we have the quest item to keep leveling?
     */
    private function canContinueLeveling(Character $character): bool
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return $inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        })->isNotEmpty();
    }

    /**
     * Get the total xp bonus.
     */
    private function getTotalXpBonus(Collection $questItems, bool $ignoreCaps): float
    {
        if ($questItems->isEmpty()) {
            return 0.0;
        }

        return $questItems->where('item.ignores_caps', $ignoreCaps)->sum('item.xp_bonus');
    }
}
