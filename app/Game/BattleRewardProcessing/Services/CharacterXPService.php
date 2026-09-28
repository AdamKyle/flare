<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Models\Monster;
use App\Game\Battle\Values\MaxLevel;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\Concerns\Boons;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Core\Values\LevelUpValue;
use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Skills\Services\SkillService;
use Closure;
use Facades\App\Game\BattleRewardProcessing\Calculators\XPCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CharacterXPService
{
    use Boons, SafelyBroadcastsEvents;

    private Character $character;

    private ?Closure $heartbeatCallback = null;

    private ?Throwable $xpCalculationFailure = null;

    private array $checkpointedProgression = [];

    private ResolvedCharacterGemEffects $resolvedCharacterGemEffects;

    /**
     * @param CharacterService $characterService
     * @param LevelUpValue $levelUpValue
     * @param SkillService $skillService
     * @param BattleMessageHandler $battleMessageHandler
     * @param CharacterAreaGemEffects $characterAreaGemEffects
     * @param CharacterGemEffects $characterGemEffects
     */
    public function __construct(
        private readonly CharacterService $characterService,
        private readonly LevelUpValue $levelUpValue,
        private readonly SkillService $skillService,
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly CharacterAreaGemEffects $characterAreaGemEffects,
        private readonly CharacterGemEffects $characterGemEffects,
    ) {}

    /**
     * Set the character.
     *
     * @param Character $character
     * @return CharacterXPService
     */
    public function setCharacter(Character $character): CharacterXPService
    {
        $this->character = $character;
        $this->xpCalculationFailure = null;
        $this->checkpointedProgression = [];
        $this->resolvedCharacterGemEffects = $this->characterGemEffects->resolveForCharacterId($character->id);

        return $this;
    }

    /**
     * Return the invalid whole-XP calculation failure recorded during the most recent XP
     * operation, if one occurred, so the caller can fail the owning reward operation instead of
     * treating a corrupted calculation as a silent or extreme XP reward.
     *
     * @return ?Throwable
     */
    public function xpCalculationFailure(): ?Throwable
    {
        return $this->xpCalculationFailure;
    }

    /**
     * Register a heartbeat callback invoked during long-running XP distribution.
     *
     * @param ?Closure $callback
     * @return self
     */
    public function withHeartbeatCallback(?Closure $callback): self
    {
        $this->heartbeatCallback = $callback;

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @param Monster $monster
     * @return CharacterXPService
     */
    public function distributeCharacterXP(Monster $monster): CharacterXPService
    {
        $this->distributeXP($monster);

        $this->handleLevelUp();

        return $this;
    }

    /**
     * Distribute a specific amount of XP
     *
     * @param int $xp
     * @return CharacterXPService
     */
    public function distributeSpecifiedXp(int $xp): CharacterXPService
    {
        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);

            return $this;
        }

        $this->character->update([
            'xp' => $this->character->xp + $xp,
        ]);

        $this->character = $this->character->refresh();

        $this->handleLevelUp();

        return $this;
    }

    /**
     * Return the ordered level, XP, and XP-next snapshots produced by the most recent checkpointed XP distribution, one per level up trigger.
     *
     * @return array
     */
    public function checkpointedProgression(): array
    {
        return $this->checkpointedProgression;
    }

    /**
     * Apply XP and every resulting level up to the Character with one write, invoking the checkpoint callback once inside the same transaction.
     *
     * @param int $xp
     * @param ?Closure $checkpointCallback
     * @return CharacterXPService
     */
    public function distributeCheckpointedXp(int $xp, ?Closure $checkpointCallback = null): CharacterXPService
    {
        $this->checkpointedProgression = [];

        DB::transaction(fn () => $this->applyCheckpointedXp($xp, $checkpointCallback));

        if (! is_null($this->heartbeatCallback)) {
            ($this->heartbeatCallback)();
        }

        if ($this->checkpointedProgression !== []) {
            CharacterAttackTypesCacheBuilder::dispatch($this->character);
        }

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
        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);

            return;
        }

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
        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);

            return;
        }

        $this->characterService->levelUpCharacter($this->character, $leftOverXP);
        $this->character = $this->character->refresh();

        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);
        }

        $character = $this->character->refresh();

        if ($shouldBuildCache || $leftOverXP < $character->xp_next) {
            CharacterAttackTypesCacheBuilder::dispatch($character);
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
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return int
     */
    public function fetchXpForMonster(Monster $monster, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): int
    {
        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);

            return 0;
        }

        $resolvedAreaGemEffects ??= $this->characterAreaGemEffects->resolveForCharacterId($this->character->id);

        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level);

        $monsterXpIncrease = $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::MONSTER_XP_INCREASE);
        $xp = $this->roundToWholeXp($xp * (1 + $monsterXpIncrease));

        if (is_null($xp)) {
            return 0;
        }

        if ($this->character->level >= $monster->max_level && $this->character->user->show_monster_to_low_level_message) {
            ServerMessageHandler::sendBasicMessage($this->character->user, $monster->name.' has a max level of: '.number_format($monster->max_level).'. You are only getting 1/3rd of: '.number_format($monster->xp).' XP before all bonuses. Move down the list child.');
        }

        $xp = $this->skillService->setSkillInTraining($this->character)->getCharacterXpWithSkillTrainingReduction($this->character, $xp);

        if ($xp === 0) {
            return 0;
        }

        return $this->getXpWithBonuses($xp, $resolvedAreaGemEffects);
    }

    /**
     * Determine the XP to reward.
     *
     * - Calculate based on two things:
     *   - All quest items that ignore the caps
     *   - All quest items that do no ignore caps
     *   - Add both together to get the XP.
     *
     * @param Character $character
     * @param int $xp
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return int
     */
    public function determineXPToAward(Character $character, int $xp, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): int
    {

        if ($xp === 0) {
            return 0;
        }

        $canContinueLeveling = $this->canContinueLeveling($character);

        $xpBonusQuestSlots = $this->findAllItemsThatGiveXpBonus($character);
        $boonBonus = $character->boons()->active()->with('itemUsed')->get()->sum(function ($boon): float {
            $itemUsed = $boon->itemUsed;

            if (is_null($itemUsed) || is_null($itemUsed->xp_bonus)) {
                return 0.0;
            }

            $amountUsed = $itemUsed->can_stack ? $boon->amount_used : 1;

            return $itemUsed->xp_bonus * $amountUsed;
        });
        $map = $character->map->gameMap;
        $mapBonus = ! is_null($map->xp_bonus) ? $map->xp_bonus : 0;
        $resolvedAreaGemEffects ??= $this->characterAreaGemEffects->resolveForCharacterId($character->id);
        $gemCharacterXpBonus = $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::CHARACTER_XP_BONUS)
            + $this->resolvedCharacterGemEffects->characterXpGain();

        $xpBonusIgnoreCaps = $this->getTotalXpBonus($xpBonusQuestSlots, true) + $boonBonus + $mapBonus + $gemCharacterXpBonus;
        $xpBonusWithCaps = $this->getTotalXpBonus($xpBonusQuestSlots, false);

        if ($canContinueLeveling) {
            return $this->continueLevelingXpWithBonuses($character, $xp, $xpBonusIgnoreCaps, $xpBonusWithCaps);
        }

        return $this->regularLevelingXpWithBonuses($character, $xp, $xpBonusIgnoreCaps, $xpBonusWithCaps);
    }

    /**
     * Can the character gain XP?
     *
     * @param Character $character
     * @return bool
     */
    public function canCharacterGainXP(Character $character): bool
    {
        return $character->level < $this->getCharacterMaxLevel($character);
    }

    /**
     * Is the character halfway to max?
     *
     * @param int $characterLevel
     * @return bool
     */
    public function isCharacterHalfWay(int $characterLevel): bool
    {
        $halfWay = MaxLevelConfiguration::first()->half_way;
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;

        return $characterLevel >= $halfWay && $characterLevel < $threeQuarters;
    }

    /**
     * Are we 75% of the way to max?
     *
     * @param int $characterLevel
     * @return bool
     */
    public function isCharacterThreeQuarters(int $characterLevel): bool
    {
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;
        $lastLeg = MaxLevelConfiguration::first()->last_leg;

        return $characterLevel >= $threeQuarters && $characterLevel < $lastLeg;
    }

    /**
     * Are we at the last 100 levels?
     *
     * @param int $characterLevel
     * @return bool
     */
    public function isCharacterAtLastLeg(int $characterLevel): bool
    {
        $lastLeg = MaxLevelConfiguration::first()->last_leg;
        $maxLevel = MaxLevelConfiguration::first()->max_level;

        return $characterLevel >= $lastLeg && $characterLevel < $maxLevel;
    }

    /**
     * Apply the XP to the locked Character row and persist every resulting level up in one Character write, then invoke the checkpoint callback.
     *
     * @param int $xp
     * @param ?Closure $checkpointCallback
     * @return void
     */
    private function applyCheckpointedXp(int $xp, ?Closure $checkpointCallback): void
    {
        $character = Character::query()->whereKey($this->character->id)->lockForUpdate()->firstOrFail();

        if (! $this->canCharacterGainXP($character)) {
            $this->character = $this->normalizeCharacterMaxLevel($character);

            $this->invokeCheckpointCallback($checkpointCallback, $xp, 0);

            return;
        }

        $startingLevel = $character->level;
        $leveledCharacter = $this->levelUpInMemory($character, $xp);

        $character->update($leveledCharacter->getDirty());

        $this->character = $character->refresh();

        $this->invokeCheckpointCallback($checkpointCallback, $xp, max(0, $this->character->level - $startingLevel));
    }

    /**
     * Add the XP to an in-memory copy of the Character and apply every level up trigger it pays for, recording one progression snapshot per trigger.
     *
     * @param Character $character
     * @param int $xp
     * @return Character
     */
    private function levelUpInMemory(Character $character, int $xp): Character
    {
        $maxLevel = $this->getCharacterMaxLevel($character);
        $levelsPerTrigger = $this->gainsAdditionalLevelOnLevelUp($character) ? $this->additionalLevelsToGain($character) : 1;

        if ($this->resolvedCharacterGemEffects->gainsAdditionalLevel()) {
            $levelsPerTrigger++;
        }

        $leveledCharacter = clone $character;
        $leveledCharacter->xp = $character->xp + $xp;

        while ($leveledCharacter->level < $maxLevel && $leveledCharacter->xp >= $leveledCharacter->xp_next) {
            $this->applyLevelUpTrigger($leveledCharacter, $maxLevel, $levelsPerTrigger);
        }

        return $leveledCharacter;
    }

    /**
     * Apply one level up trigger to the in-memory Character and record its progression snapshot.
     *
     * @param Character $leveledCharacter
     * @param int $maxLevel
     * @param int $levelsPerTrigger
     * @return void
     */
    private function applyLevelUpTrigger(Character $leveledCharacter, int $maxLevel, int $levelsPerTrigger): void
    {
        $levelUpValues = $this->levelUpValue->createValueObjectForResolvedRules(
            $leveledCharacter,
            $leveledCharacter->xp - $leveledCharacter->xp_next,
            $maxLevel,
            $levelsPerTrigger,
        );

        $levelUpValues['xp_next'] = $this->xpRequiredAfterLevel($levelUpValues['level'], $leveledCharacter->xp_penalty);

        // The per-level path re-read these from decimal(12,4) columns after every level up, so round each trigger to the stored precision.
        $levelUpValues['base_stat_mod'] = round($levelUpValues['base_stat_mod'], 4);
        $levelUpValues['base_damage_stat_mod'] = round($levelUpValues['base_damage_stat_mod'], 4);

        $leveledCharacter->fill($levelUpValues);

        $this->checkpointedProgression[] = [
            'level' => $leveledCharacter->level,
            'xp' => $leveledCharacter->xp,
            'xp_next' => $leveledCharacter->xp_next,
        ];
    }

    /**
     * Return the penalised XP required to leave the given level, matching the value the Character row stores after a level up.
     *
     * @param int $level
     * @param ?float $xpPenalty
     * @return float
     */
    private function xpRequiredAfterLevel(int $level, ?float $xpPenalty): float
    {
        $nextLevelXp = $this->characterService->getXPForNextLevel($level + 1);

        // The xp_next bigint column rounds the penalised requirement when it is written, so round here to keep in-memory comparisons identical to the stored value.
        return round($nextLevelXp + $nextLevelXp * $xpPenalty);
    }

    /**
     * Invoke the checkpoint callback, when one was supplied, with the applied XP, levels awarded, Character, and progression timeline.
     *
     * @param ?Closure $checkpointCallback
     * @param int $appliedXp
     * @param int $levelsAwarded
     * @return void
     */
    private function invokeCheckpointCallback(?Closure $checkpointCallback, int $appliedXp, int $levelsAwarded): void
    {
        if (is_null($checkpointCallback)) {
            return;
        }

        $checkpointCallback($appliedXp, $levelsAwarded, $this->character, $this->checkpointedProgression);
    }

    /**
     * Handle instances where we could have multiple level ups.
     *
     * @param int $leftOverXP
     * @param bool $shouldBuildCache
     * @return void
     */
    private function handleMultipleLevelUps(int $leftOverXP, bool $shouldBuildCache = false): void
    {

        $this->handleCharacterLevelUp($leftOverXP, $shouldBuildCache);

        if (! is_null($this->heartbeatCallback)) {
            ($this->heartbeatCallback)();
        }

        $this->character = $this->character->refresh();

        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);

            return;
        }

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

        if (! $this->canCharacterGainXP($this->character)) {
            $this->character = $this->normalizeCharacterMaxLevel($this->character);

            return;
        }

        $xp = $this->fetchXpForMonster($monster);

        if (! is_null($this->xpCalculationFailure)) {
            return;
        }

        $this->character->update([
            'xp' => $this->character->xp + $xp,
        ]);

        $this->character = $this->character->refresh();

        $this->battleMessageHandler->handleXPMessage($this->character->user, $xp, $this->character->xp);
    }

    /**
     * Fetch XP with additional bonuses.
     *
     * - Applies Guide Quest XP (+10 while under level 2)
     * - Applies Addional bonuses from items and quest items.
     *
     * @param int $xp
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return int
     */
    private function getXpWithBonuses(int $xp, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): int
    {
        $xp = $this->determineXPToAward($this->character, $xp, $resolvedAreaGemEffects);

        $guideEnabled = $this->character->user->guide_enabled;
        $hasNoCompletedGuideQuests = $this->character->questsCompleted()
            ->whereNotNull('guide_quest_id')
            ->get()
            ->isEmpty();

        if ($guideEnabled && $hasNoCompletedGuideQuests && $this->character->level < 2) {
            $xp += 10;

            $this->safelyDispatchBroadcastEvent(
                new ServerMessageEvent($this->character->user, 'Rewarded an extra 10XP while doing the first guide quest. This bonus will end after you reach level 2.'),
                ['character_id' => $this->character->id]
            );
        }

        return $xp;
    }

    /**
     * Get xp when we can continue leveling.
     *
     * @param Character $character
     * @param int $xp
     * @param float $xpBonusIgnoreCaps
     * @param float $xpBonusWithCaps
     * @return int
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
     *
     * @param Character $character
     * @param int $xp
     * @param float $xpBonusIgnoreCaps
     * @param float $xpBonusWithCaps
     * @return int
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
     *
     * @param Character $character
     * @param bool $ignoreCaps
     * @param float $xpBonus
     * @param int $xp
     * @return float
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
     *
     * @param Character $character
     * @return Collection
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
     *
     * @param Character $character
     * @return bool
     */
    private function canContinueLeveling(Character $character): bool
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return $inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::CONTINUE_LEVELING->value;
        })->isNotEmpty();
    }

    /**
     * Get the total xp bonus.
     *
     * @param Collection $questItems
     * @param bool $ignoreCaps
     * @return float
     */
    private function getTotalXpBonus(Collection $questItems, bool $ignoreCaps): float
    {
        if ($questItems->isEmpty()) {
            return 0.0;
        }

        return $questItems->where('item.ignores_caps', $ignoreCaps)->sum('item.xp_bonus');
    }

    /**
     * Get the character max level.
     *
     * @param Character $character
     * @return int
     */
    private function getCharacterMaxLevel(Character $character): int
    {
        if (! $this->canContinueLeveling($character)) {
            return MaxLevel::MAX_LEVEL;
        }

        $config = MaxLevelConfiguration::first();

        if (is_null($config)) {
            return MaxLevel::MAX_LEVEL;
        }

        return $config->max_level;
    }

    /**
     * Normalize character level and XP when maxed.
     *
     * @param Character $character
     * @return Character
     */
    private function normalizeCharacterMaxLevel(Character $character): Character
    {
        $maxLevel = $this->getCharacterMaxLevel($character);

        if ($character->level > $maxLevel) {
            $character->update([
                'level' => $maxLevel,
                'xp' => 0,
            ]);

            return $character->refresh();
        }

        if ($character->level === $maxLevel && $character->xp !== 0) {
            $character->update([
                'xp' => 0,
            ]);

            return $character->refresh();
        }

        return $character->refresh();
    }

    /**
     * Round a Gem-adjusted floating XP calculation to its nearest whole XP amount, or null when
     * the calculation is invalid. The game's XP domain never exceeds the platform integer range,
     * so a failed validation means the calculation that produced this amount is corrupted; that
     * failure is recorded on `xpCalculationFailure()` instead of silently applying a zero,
     * `PHP_INT_MAX`, or `PHP_INT_MIN` XP reward, so the caller can fail the owning reward operation.
     *
     * @param float $xp
     * @return ?int
     */
    private function roundToWholeXp(float $xp): ?int
    {
        $wholeXp = filter_var(round($xp), FILTER_VALIDATE_INT);

        if ($wholeXp === false) {
            $this->xpCalculationFailure = new RuntimeException(
                'Invalid whole XP amount calculated: '.$xp.' cannot be represented as an integer.',
            );

            return null;
        }

        return $wholeXp;
    }
}
