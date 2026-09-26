<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\CharacterBoon;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\Models\Skill;
use App\Flare\Models\Traits\CalculateSkillBonus;
use App\Flare\Models\Traits\CalculateTimeReduction;
use App\Game\Skills\Contracts\SkillBonusQuery;
use App\Game\Skills\Values\SkillBonusAttribute;
use App\Game\Skills\Values\SkillBonusItemSource;
use App\Game\Skills\Values\SkillBoonBonusAttribute;
use Illuminate\Support\Collection;

class SkillBonusService implements SkillBonusQuery
{
    use CalculateSkillBonus, CalculateTimeReduction;

    /**
     * @param SkillBonusContextService $skillBonusContextService
     */
    public function __construct(
        private readonly SkillBonusContextService $skillBonusContextService,
    ) {}

    /**
     * Return the Skill's current total bonus including item, boon, and Class contributions.
     *
     * @param Skill $skill
     * @return float
     */
    public function skillBonus(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        if (is_null($skill->baseSkill->skill_bonus_per_level)) {
            return 0.0;
        }

        $totalBonus = $this->levelSkillBonus($skill)
            + $this->itemBonusTotal($skill, SkillBonusAttribute::SKILL_BONUS)
            + $this->characterBoonsBonus($skill, SkillBoonBonusAttribute::INCREASE_SKILL_BONUS_BY)
            + $this->classSkillModifier($skill);

        if ($totalBonus > 1.0) {
            return 1.0;
        }

        return $totalBonus + $this->classSpecificTrainingBonus($skill);
    }

    /**
     * Return the Skill's total training bonus from items, boons, and Class specific training bonuses.
     *
     * @param Skill $skill
     * @return float
     */
    public function skillTrainingBonus(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        return $this->itemBonusTotal($skill, SkillBonusAttribute::SKILL_TRAINING_BONUS)
            + $this->characterBoonsBonus($skill, SkillBoonBonusAttribute::INCREASE_SKILL_TRAINING_BONUS_BY)
            + $this->classSpecificTrainingBonus($skill);
    }

    /**
     * Return the Skill's total base damage modifier from its level, equipped items, and boons.
     *
     * @param Skill $skill
     * @return float
     */
    public function baseDamageMod(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        return $this->baseModifier(
            $skill,
            $skill->baseSkill->base_damage_mod_bonus_per_level,
            SkillBonusAttribute::BASE_DAMAGE_MOD,
            SkillBoonBonusAttribute::BASE_DAMAGE_MOD_BONUS,
        );
    }

    /**
     * Return the Skill's total base healing modifier from its level, equipped items, and boons.
     *
     * @param Skill $skill
     * @return float
     */
    public function baseHealingMod(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        return $this->baseModifier(
            $skill,
            $skill->baseSkill->base_healing_mod_bonus_per_level,
            SkillBonusAttribute::BASE_HEALING_MOD,
            SkillBoonBonusAttribute::BASE_HEALING_MOD_BONUS,
        );
    }

    /**
     * Return the Skill's total base AC modifier from its level, equipped items, and boons.
     *
     * @param Skill $skill
     * @return float
     */
    public function baseAcMod(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        return $this->baseModifier(
            $skill,
            $skill->baseSkill->base_ac_mod_bonus_per_level,
            SkillBonusAttribute::BASE_AC_MOD,
            SkillBoonBonusAttribute::BASE_AC_MOD_BONUS,
        );
    }

    /**
     * Return the Skill's fight timeout modifier, capped at its maximum allowed reduction.
     *
     * @param Skill $skill
     * @return float
     */
    public function fightTimeOutMod(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        $valuePerLevel = $skill->baseSkill->fight_time_out_mod_bonus_per_level;

        if (is_null($valuePerLevel) || ! ($valuePerLevel > 0.0)) {
            return 0.0;
        }

        $totalBonus = $this->calculateTotalTimeBonus($skill, 'fight_time_out_mod_bonus_per_level')
            + $this->itemBonusTotal($skill, SkillBonusAttribute::FIGHT_TIME_OUT_MOD_BONUS, true)
            + $valuePerLevel;

        return min($totalBonus, 0.50);
    }

    /**
     * Return the Skill's move timeout modifier, capped at its maximum allowed reduction.
     *
     * @param Skill $skill
     * @return float
     */
    public function moveTimeOutMod(Skill $skill): float
    {
        $this->skillBonusContextService->clearCachedContext();

        $valuePerLevel = $skill->baseSkill->move_time_out_mod_bonus_per_level;

        if (is_null($valuePerLevel) || ! ($valuePerLevel > 0.0)) {
            return 0.0;
        }

        $totalBonus = $valuePerLevel
            + $this->itemBonusTotal($skill, SkillBonusAttribute::MOVE_TIME_OUT_MOD_BONUS, true)
            + $this->calculateTotalTimeBonus($skill, 'move_time_out_mod_bonus_per_level');

        return min($totalBonus, 1.0);
    }

    /**
     * Build the equipped and quest items contributing a positive bonus to the Skill for the attribute.
     *
     * @param Skill $skill
     * @param SkillBonusAttribute $skillBonusAttribute
     * @return array
     */
    public function itemBonusBreakdown(Skill $skill, SkillBonusAttribute $skillBonusAttribute): array
    {
        $this->skillBonusContextService->clearCachedContext();

        $equippedEntries = $this->buildItemBonusBreakdownEntries(
            $this->skillBonusContextService->equippedSlotsWithItems($skill),
            SkillBonusItemSource::EQUIPPED,
            $skill,
            $skillBonusAttribute,
        );

        $questEntries = $this->buildItemBonusBreakdownEntries(
            $this->skillBonusContextService->questSlotsWithItems($skill),
            SkillBonusItemSource::QUEST,
            $skill,
            $skillBonusAttribute,
        );

        return array_merge($equippedEntries, $questEntries);
    }

    /**
     * Return the Skill's level-based bonus, which is complete once the Skill reaches its max level.
     *
     * @param Skill $skill
     * @return float
     */
    private function levelSkillBonus(Skill $skill): float
    {
        $maxLevel = $skill->baseSkill->max_level;
        $level = min(max($skill->level, 1), $maxLevel);

        if ($level >= $maxLevel) {
            return 1.0;
        }

        return $skill->baseSkill->skill_bonus_per_level * ($level - 1);
    }

    /**
     * Return a base modifier built from the Skill's level, its equipped items, and the Character's boons.
     *
     * @param Skill $skill
     * @param float|null $valuePerLevel
     * @param SkillBonusAttribute $itemAttribute
     * @param SkillBoonBonusAttribute $boonAttribute
     * @return float
     */
    private function baseModifier(Skill $skill, ?float $valuePerLevel, SkillBonusAttribute $itemAttribute, SkillBoonBonusAttribute $boonAttribute): float
    {
        if (is_null($valuePerLevel) || ! ($valuePerLevel > 0.0)) {
            return 0.0;
        }

        $levelAndBoonBonus = $valuePerLevel * $skill->level + $this->characterBoonsBonus($skill, $boonAttribute);

        return $this->itemBonusTotal($skill, $itemAttribute, true) + $levelAndBoonBonus;
    }

    /**
     * Return the Character's Class modifier for the Accuracy, Looting, and Dodge Skills.
     *
     * @param Skill $skill
     * @return float
     */
    private function classSkillModifier(Skill $skill): float
    {
        $character = $skill->character;

        return match ($skill->baseSkill->name) {
            'Accuracy' => $character->class->accuracy_mod ?? 0.0,
            'Looting' => $character->class->looting_mod ?? 0.0,
            'Dodge' => $character->class->dodge_mod ?? 0.0,
            default => 0.0,
        };
    }

    /**
     * Return the flat training bonus granted to a Class for its specialised crafting Skills.
     *
     * @param Skill $skill
     * @return float
     */
    private function classSpecificTrainingBonus(Skill $skill): float
    {
        $classType = $this->skillBonusContextService->gameClass($skill->character)->type();

        $isClassCraftingSkill = match ($skill->baseSkill->name) {
            'Weapon Crafting', 'Armour Crafting', 'Ring Crafting' => $classType->isBlacksmith(),
            'Spell Crafting', 'Alchemy' => $classType->isArcaneAlchemist(),
            default => false,
        };

        if (! $isClassCraftingSkill) {
            return 0.0;
        }

        return 0.15;
    }

    /**
     * Return the total bonus for the attribute from equipped items and, unless excluded, quest items.
     *
     * @param Skill $skill
     * @param SkillBonusAttribute $skillBonusAttribute
     * @param bool $equippedOnly
     * @return float
     */
    private function itemBonusTotal(Skill $skill, SkillBonusAttribute $skillBonusAttribute, bool $equippedOnly = false): float
    {
        $bonus = $this->addSlotsBonus(0.0, $this->skillBonusContextService->equippedSlotsWithItems($skill), $skill, $skillBonusAttribute);

        if ($equippedOnly) {
            return $bonus;
        }

        return $this->addSlotsBonus($bonus, $this->skillBonusContextService->questSlotsWithItems($skill), $skill, $skillBonusAttribute);
    }

    /**
     * Add the bonus each slot's item gives the Skill for the attribute onto the running total.
     *
     * @param float $runningBonus
     * @param Collection $slots
     * @param Skill $skill
     * @param SkillBonusAttribute $skillBonusAttribute
     * @return float
     */
    private function addSlotsBonus(float $runningBonus, Collection $slots, Skill $skill, SkillBonusAttribute $skillBonusAttribute): float
    {
        return $slots->reduce(
            fn (float $total, InventorySlot|SetSlot $slot): float => $total + $this->calculateBonus($slot->item, $skill->baseSkill, $skillBonusAttribute->value),
            $runningBonus,
        );
    }

    /**
     * Return the total bonus the Character's active boons give for the attribute.
     *
     * @param Skill $skill
     * @param SkillBoonBonusAttribute $boonAttribute
     * @return float
     */
    private function characterBoonsBonus(Skill $skill, SkillBoonBonusAttribute $boonAttribute): float
    {
        return $this->skillBonusContextService->activeBoonsWithItemUsed($skill)->reduce(
            fn (float $total, CharacterBoon $boon): float => $total + $this->boonBonus($boon, $boonAttribute),
            0.0,
        );
    }

    /**
     * Return one boon's bonus for the attribute, multiplied by its stacks when its item can stack.
     *
     * @param CharacterBoon $boon
     * @param SkillBoonBonusAttribute $boonAttribute
     * @return float
     */
    private function boonBonus(CharacterBoon $boon, SkillBoonBonusAttribute $boonAttribute): float
    {
        $itemUsed = $boon->itemUsed;

        if (is_null($itemUsed)) {
            return 0.0;
        }

        $value = $itemUsed->{$boonAttribute->value};

        if (is_null($value)) {
            return 0.0;
        }

        $amountUsed = $itemUsed->can_stack ? $boon->amount_used : 1;

        return $value * $amountUsed;
    }

    /**
     * Build the breakdown entries for every slot from one item source that contributes a positive bonus.
     *
     * @param Collection $slots
     * @param SkillBonusItemSource $source
     * @param Skill $skill
     * @param SkillBonusAttribute $skillBonusAttribute
     * @return array
     */
    private function buildItemBonusBreakdownEntries(Collection $slots, SkillBonusItemSource $source, Skill $skill, SkillBonusAttribute $skillBonusAttribute): array
    {
        return $slots->map(
            fn (InventorySlot|SetSlot $slot): ?array => $this->buildItemBonusBreakdownEntry($slot, $source, $skill, $skillBonusAttribute)
        )->filter()->values()->all();
    }

    /**
     * Build the breakdown entry identifying one slot's item and its bonus, or null when it contributes nothing.
     *
     * @param InventorySlot|SetSlot $slot
     * @param SkillBonusItemSource $source
     * @param Skill $skill
     * @param SkillBonusAttribute $skillBonusAttribute
     * @return array|null
     */
    private function buildItemBonusBreakdownEntry(InventorySlot|SetSlot $slot, SkillBonusItemSource $source, Skill $skill, SkillBonusAttribute $skillBonusAttribute): ?array
    {
        $bonus = $this->calculateBonus($slot->item, $skill->baseSkill, $skillBonusAttribute->value);

        if ($bonus <= 0) {
            return null;
        }

        return [
            'item_id' => $slot->item->id,
            'slot_id' => $slot->id,
            'source' => $source->value,
            'name' => $slot->item->affix_name,
            'type' => $slot->item->type,
            'position' => $slot->position,
            'affix_count' => $slot->item->affix_count,
            'is_unique' => $slot->item->is_unique,
            'is_mythic' => $slot->item->is_mythic,
            'is_cosmic' => $slot->item->is_cosmic,
            'holy_stacks_applied' => $slot->item->holy_stacks_applied,
            $skillBonusAttribute->value => $bonus,
        ];
    }
}
