<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use Illuminate\Support\Collection;

class MassDisenchantService
{
    private int $goldDust = 0;

    private int $goldDustRushGain = 0;

    private int $skillXP = 0;

    private int $disenchantingLeveledTimes = 0;

    private int $enchantingLevelTimes = 0;

    private float|int $baseSkillXP = 0;

    private Character $character;

    private Skill $disenchantingSkill;

    private Skill $enchantingSkill;

    private SkillCheckService $skillCheckService;

    private ?InventorySlot $questSlot = null;

    public function __construct(SkillCheckService $skillCheckService)
    {
        $this->skillCheckService = $skillCheckService;
    }

    /**
     * Set up the service.
     */
    public function setUp(Character $character): MassDisenchantService
    {
        $this->character = $character;

        $this->disenchantingSkill = $character->skills->filter(function ($skill) {
            return $skill->type()->isDisenchanting();
        })->first();

        $this->enchantingSkill = $character->skills->filter(function ($skill) {
            return $skill->type()->isEnchanting();
        })->first();

        $this->questSlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::GOLD_DUST_RUSH;
        })->first();

        $this->baseSkillXP = 25 + 25 * $this->disenchantingSkill->skill_training_bonus;

        return $this;
    }

    /**
     * Get the amount of times the disenchanting skill leveled.
     */
    public function getDisenchantingTimesLeveled(): int
    {
        return $this->disenchantingLeveledTimes;
    }

    /**
     * Get the amount of times the enchanting skill leveled.
     */
    public function getEnchantingTimesLeveled(): int
    {
        return $this->enchantingLevelTimes;
    }

    /**
     * Get the total gold dust.
     */
    public function getTotalGoldDust(): int
    {
        return $this->goldDust;
    }

    /**
     * Disenchant the items.
     */
    public function disenchantItems(Collection $slots): void
    {

        foreach ($slots as $slot) {
            $this->disenchantItem($slot);
        }

        if ($this->goldDustRushGain > 0 && ! is_null($this->questSlot) && $this->fetchDCRoll() === 100) {
            $this->goldDust += (int) floor($this->goldDustRushGain * 0.05);
        }

        $newGoldDust = $this->character->gold_dust + $this->goldDust;

        if ($newGoldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character = $this->character;
        $this->skillXP += $this->disenchantingSkill->xp;

        $this->disenchantingSkill = $this->giveXpToSkill($this->disenchantingSkill, $this->skillXP, 'disenchantingLeveledTimes');

        $this->enchantingSkill = $this->giveXpToSkill($this->enchantingSkill, $this->skillXP / 2, 'enchantingLevelTimes');

        $character->update([
            'gold_dust' => $newGoldDust,
        ]);

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new UpdateCharacterBaseDetailsEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));
    }

    /**
     * Give XP to the skills.
     */
    protected function giveXpToSkill(Skill $skill, int $leftOver, string $leveledType): Skill
    {
        if ($skill->level >= $skill->baseSkill->max_level) {
            return $this->normalizeMaxLevelSkill($skill, $leveledType);
        }

        while ($leftOver >= $skill->xp_max && $skill->level < $skill->baseSkill->max_level) {
            $leftOver -= $skill->xp_max;
            $this->levelUpSkill($skill, $leveledType);

            $skill = $skill->refresh();
        }

        if ($skill->level >= $skill->baseSkill->max_level) {
            return $this->normalizeMaxLevelSkill($skill, $leveledType);
        }

        $skill->update([
            'xp' => $leftOver,
        ]);

        return $this->refreshSkillForLeveledType($skill, $leveledType);
    }

    protected function levelUpSkill(Skill $skill, string $leveledType): void
    {
        if ($skill->level >= $skill->baseSkill->max_level) {
            $this->normalizeMaxLevelSkill($skill, $leveledType);

            return;
        }

        $level = min($skill->level + 1, $skill->baseSkill->max_level);

        $bonus = $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level;

        if ($skill->baseSkill->max_level === $level) {
            $bonus = 1.0;
        }

        $skill->update([
            'level' => $level,
            'xp_max' => $skill->can_train ? $level * 10 : rand(100, 350),
            'base_damage_mod' => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
            'base_healing_mod' => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
            'base_ac_mod' => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
            'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
            'move_time_out_mod' => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
            'skill_bonus' => $bonus,
            'xp' => 0,
        ]);

        $this->{$leveledType} += 1;
    }

    /**
     * Disenchant the item.
     */
    protected function disenchantItem(InventorySlot $slot): void
    {
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);
        $roll = $this->skillCheckService->characterRoll($this->disenchantingSkill);

        if ($roll > $dcCheck) {

            if (! ($this->goldDust >= MaxCurrenciesValue::MAX_GOLD_DUST)) {
                $goldDust = $this->updateGoldDust();
                $this->goldDust += $goldDust;
                $this->goldDustRushGain += $goldDust;
            }

            $this->skillXP += $this->getSkillXp();

            $slot->delete();

            return;
        }

        if (! ($this->goldDust >= MaxCurrenciesValue::MAX_GOLD_DUST)) {
            $this->goldDust += $this->updateGoldDust(true);
        }

        $slot->delete();
    }

    /**
     * Get the skill XP.
     */
    protected function getSkillXp(): float|int
    {
        $gameMap = $this->character->map->gameMap;

        $skillXP = $this->baseSkillXP;

        if (! is_null($gameMap->skill_training_bonus)) {
            $skillXP = $skillXP + $skillXP * $gameMap->skill_training_bonus;
        }

        return $skillXP;
    }

    /**
     * Return the new amount of gold dust.
     */
    protected function updateGoldDust(bool $failedCheck = false): int
    {
        $goldDust = ! $failedCheck ? $this->fetchGoldDustAmount() : 1;

        if (! $failedCheck) {
            $goldDust = (int) ($goldDust + $goldDust * $this->disenchantingSkill->bonus);
        }

        return $goldDust;
    }

    protected function fetchGoldDustAmount(): int
    {
        return rand(2, 1150);
    }

    /**
     * fetch the DC roll.
     */
    protected function fetchDCRoll(): int
    {
        return rand(1, 100);
    }

    protected function normalizeMaxLevelSkill(Skill $skill, string $leveledType): Skill
    {
        $skill->update([
            'level' => $skill->baseSkill->max_level,
            'xp' => 0,
        ]);

        return $this->refreshSkillForLeveledType($skill, $leveledType);
    }

    protected function refreshSkillForLeveledType(Skill $skill, string $leveledType): Skill
    {
        $skill = $skill->refresh();

        if ($leveledType === 'enchantingLevelTimes') {
            $this->enchantingSkill = $skill;

            return $skill;
        }

        $this->disenchantingSkill = $skill;

        return $skill;
    }
}
