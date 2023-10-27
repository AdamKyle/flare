<?php

namespace App\Game\Skills\Services;

use App\Game\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Traits\MercenaryBonus;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Support\Collection;

class MassDisenchantService {

    use MercenaryBonus;

    /**
     * @var int $goldDust
     */
    private int $goldDust = 0;

    /**
     * @var int $skillXP
     */
    private int $skillXP = 0;

    /**
     * @var int $disenchantingLeveledTimes
     */
    private int $disenchantingLeveledTimes = 0;

    /**
     * @var int $enchantingLevelTimes
     */
    private int $enchantingLevelTimes = 0;

    /**
     * @var float $goldDustBonus
     */
    private float $goldDustBonus = 0.0;

    /**
     * @var float|int $baseSkillXP
     */
    private float|int $baseSkillXP = 0;

    /**
     * @var EnchantingService $enchantingService
     */
    private EnchantingService $enchantingService;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var Skill $disenchantingSkill
     */
    private Skill $disenchantingSkill;

    /**
     * @var Skill $enchantingSkill
     */
    private Skill $enchantingSkill;

    private SkillCheckService $skillCheckService;

    /**
     * @var InventorySlot|null $questSlot
     */
    private ?InventorySlot $questSlot = null;

    public function __construct(SkillCheckService $skillCheckService) {
        $this->skillCheckService = $skillCheckService;
    }

    /**
     * Set up the service.
     *
     * @param Character $character
     * @return MassDisenchantService
     */
    public function setUp(Character $character): MassDisenchantService {
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

        $this->goldDustBonus = $this->getGoldDustBonus($character);

        $this->baseSkillXP   = 25 + 25 * $this->disenchantingSkill->skill_training_bonus;

        return $this;
    }

    /**
     * Get the amount of times the disenchanting skill leveled.
     *
     * @return int
     */
    public function getDisenchantingTimesLeveled(): int {
        return $this->disenchantingLeveledTimes;
    }

    /**
     * Get the amount of times the enchanting skill leveled.
     *
     * @return int
     */
    public function getEnchantingTimesLeveled(): int {
        return $this->enchantingLevelTimes;
    }

    /**
     * Get the total gold dust.
     *
     * @return int
     */
    public function getTotalGoldDust(): int {
        return $this->goldDust;
    }

    /**
     * Disenchant the items.
     *
     * @param Collection $slots
     * @return void
     */
    public function disenchantItems(Collection $slots): void {

        foreach ($slots as $slot) {
            $this->disenchantItem($slot);
        }

        $newGoldDust = $this->character->gold_dust + $this->goldDust;

        if ($newGoldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character      = $this->character;
        $this->skillXP += $this->disenchantingSkill->xp;

        if ($this->disenchantingSkill->level < $this->disenchantingSkill->baseSkill->max_level) {
            $this->giveXpToSkill($this->disenchantingSkill, $this->skillXP, 'disenchantingLeveledTimes');
        }

        if ($this->enchantingSkill->level < $this->enchantingSkill->baseSkill->max_level) {
            $this->giveXpToSkill($this->enchantingSkill, $this->skillXP / 2, 'enchantingLevelTimes');
        }

        $character->update([
            'gold_dust' => $newGoldDust,
        ]);

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new UpdateTopBarEvent($character));
    }

    /**
     * Give XP to the skills.
     *
     * @param Skill $skill
     * @param int $leftOver
     * @param string $leveledType
     * @return Skill
     */
    protected function giveXpToSkill(Skill $skill, int $leftOver, string $leveledType): Skill {

        if ($leftOver >= $skill->xp_max) {

            $leftOver = $leftOver - $skill->xp_max;
            $this->levelUpSkill($skill, $leveledType);

            $skill = $skill->refresh();

            if ($leftOver >= $skill->xp_max && ($skill->level < $skill->baseSkill->max_level)) {
                $this->giveXpToSkill($skill, $leftOver, $leveledType);
            }
        }

        if ($leftOver >= 0 && $leftOver < $skill->xp_next && ($skill->level < $skill->baseSkill->max_level)) {
            $skill->update([
                'xp' => $skill->xp + $leftOver,
            ]);
        }

        if ($leveledType === 'enchantingLevelTimes') {
            $this->enchantingSkill = $skill->refresh();

            return $skill;
        }

        $this->disenchantingSkill = $skill->refresh();

        return $skill;
    }

    protected function levelUpSkill(Skill $skill, string $leveledType): void {

        $level = $skill->level + 1;

        $bonus = $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level;

        if ($skill->baseSkill->max_level === $level) {
            $bonus = 1.0;
        }

        $skill->update([
            'level'              => $level,
            'xp_max'             => $skill->can_train ? $level * 10 : rand(100, 350),
            'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
            'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
            'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
            'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
            'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
            'skill_bonus'        => $bonus,
            'xp'                 => 0,
        ]);

        $this->{$leveledType} += 1;
    }

    /**
     * Disenchant the item.
     *
     * @param InventorySlot $slot
     * @return void
     */
    protected function disenchantItem(InventorySlot $slot): void {
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);
        $roll    = $this->skillCheckService->characterRoll($this->disenchantingSkill);

        if ($roll > $dcCheck) {

            if (!($this->goldDust >= MaxCurrenciesValue::MAX_GOLD_DUST)) {
                $this->goldDust += $this->updateGoldDust();
            }

            $this->skillXP +=  $this->getSkillXp();

            $slot->delete();

            return;
        }

        if (!($this->goldDust >= MaxCurrenciesValue::MAX_GOLD_DUST)) {
            $this->goldDust += $this->updateGoldDust();
        }

        $slot->delete();
    }

    /**
     * Get the skill XP.
     *
     * @return float|int
     */
    protected function getSkillXp(): float|int {
        $gameMap = $this->character->map->gameMap;

        $skillXP = $this->baseSkillXP;

        if (!is_null($gameMap->skill_training_bonus)) {
            $skillXP = $skillXP + $skillXP * $gameMap->skill_training_bonus;
        }

        return $skillXP;
    }

    /**
     * Return the new amount of gold dust.
     *
     * @param bool $failedCheck
     * @return int
     */
    protected function updateGoldDust(bool $failedCheck = false): int {
        $goldDust = !$failedCheck ? rand(2, 15) : 1;

        if (!$failedCheck) {
            $goldDust = $goldDust + $goldDust * $this->disenchantingSkill->bonus;
        }

        $goldDust               = $goldDust + $goldDust * $this->goldDustBonus;
        $characterTotalGoldDust = $this->character->gold_dust;

        if (!is_null($this->questSlot) && !$failedCheck) {
            $dc   = 1000 - 1000 * 0.02;
            $roll = $this->fetchDCRoll();

            if ($roll > $dc) {;

                $goldDust = $characterTotalGoldDust * 0.05;

                return $goldDust;
            }
        }

        return $goldDust;
    }

    /**
     * fetch the DC roll.
     *
     * @return int
     */
    protected function fetchDCRoll(): int {
        return rand(1, 1000);
    }
}
