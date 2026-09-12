<?php

namespace App\Flare\Models;

use App\Flare\Models\Traits\CalculateSkillBonus;
use App\Flare\Models\Traits\CalculateTimeReduction;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Values\SkillTypeValue;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Skill extends Model
{
    use CalculateSkillBonus, CalculateTimeReduction, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_skill_id',
        'currently_training',
        'is_locked',
        'level',
        'xp',
        'xp_max',
        'xp_towards',
        'skill_type',
        'is_hidden',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'currently_training' => 'boolean',
        'is_locked' => 'boolean',
        'level' => 'integer',
        'xp' => 'integer',
        'xp_max' => 'integer',
        'skill_type' => 'integer',
        'xp_towards' => 'float',
        'is_hidden' => 'boolean',
    ];

    protected $appends = [
        'name',
        'class_bonus',
        'class_id',
    ];

    /**
     * Resolve this Skill's type from its base Game Skill.
     *
     * @return SkillTypeValue Domain type classification of this Skill's base Game Skill.
     */
    public function type(): SkillTypeValue
    {
        return $this->baseSkill->skillType();
    }

    /**
     * The Game Skill this Character Skill is based on.
     *
     * @return BelongsTo
     */
    public function baseSkill()
    {
        return $this->belongsTo(GameSkill::class, 'game_skill_id', 'id');
    }

    /**
     * The Character who owns this Skill.
     *
     * @return BelongsTo
     */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Build the list of equipped/quest items contributing to this Skill's bonus.
     *
     * @param string $skillAttribute Skill attribute whose contributing items are being resolved.
     * @return array Item bonus breakdown entries, each describing the contributing item and its bonus amount.
     */
    public function getItemSkillBreakdown(string $skillAttribute = 'skill_bonus'): array
    {
        return $this->getItemBonusBreakDown($this->baseSkill, $skillAttribute);
    }

    /**
     * Resolve this Skill's display name from its base Game Skill.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->baseSkill->name;
    }

    /**
     * Resolve this Skill's total Class bonus for its current level.
     *
     * @return float|int
     */
    public function getClassBonusAttribute()
    {

        if (is_null($this->baseSkill->class_bonus)) {
            return 0;
        }

        return $this->baseSkill->class_bonus * $this->level;
    }

    /**
     * Resolve the Game Class id associated with this Skill's base Game Skill.
     *
     * @return int|null
     */
    public function getClassIdAttribute()
    {
        if (is_null($this->baseSkill->game_class_id)) {
            return null;
        }

        return $this->baseSkill->game_class_id;
    }

    /**
     * Resolve this Skill's description from its base Game Skill.
     *
     * @return string|null
     */
    public function getDescriptionAttribute()
    {
        return $this->baseSkill->description;
    }

    /**
     * Resolve this Skill's max level from its base Game Skill.
     *
     * @return int
     */
    public function getMaxLevelAttribute()
    {
        return $this->baseSkill->max_level;
    }

    /**
     * Resolve whether this Skill can be trained from its base Game Skill.
     *
     * @return bool
     */
    public function getCanTrainAttribute()
    {
        return $this->baseSkill->can_train;
    }

    /**
     * Determine whether this Skill reduces fight time at its current level.
     *
     * @return bool
     */
    public function getReducesTimeAttribute()
    {
        $value = $this->baseSkill->fight_time_out_mod_bonus_per_level;

        if (is_null($value)) {
            return false;
        }

        if ($value <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Resolve this Skill's total unit time reduction at its current level.
     *
     * @return float|int
     */
    public function getUnitTimeReductionAttribute()
    {
        return $this->baseSkill->unit_time_reduction * $this->level;
    }

    /**
     * Resolve this Skill's total building time reduction at its current level.
     *
     * @return float|int
     */
    public function getBuildingTimeReductionAttribute()
    {
        return $this->baseSkill->building_time_reduction * $this->level;
    }

    /**
     * Resolve this Skill's total unit movement time reduction at its current level.
     *
     * @return float|int
     */
    public function getUnitMovementTimeReductionAttribute()
    {
        return $this->baseSkill->unit_movement_time_reduction * $this->level;
    }

    /**
     * Determine whether this Skill reduces movement time at its current level.
     *
     * @return bool
     */
    public function getReducesMovementTimeAttribute()
    {

        $value = $this->baseSkill->move_time_out_mod_bonus_per_level;

        if (is_null($value)) {
            return false;
        }

        if ($value <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Resolve this Skill's total base damage modifier from its base bonus and item bonuses.
     *
     * @return float
     */
    public function getBaseDamageModAttribute()
    {

        $value = $this->baseSkill->base_damage_mod_bonus_per_level;

        if (is_null($value) || ! ($value > 0.0)) {
            return 0.0;
        }

        $itemBonus = $this->getItemBonuses($this->baseSkill, 'base_damage_mod', true);

        $baseBonus = (
            $value * $this->level
        );

        $baseBonus += $this->getCharacterBoonsBonus('base_damage_mod_bonus');

        return $itemBonus + $baseBonus;
    }

    /**
     * Resolve this Skill's total base healing modifier from its base bonus and item bonuses.
     *
     * @return float
     */
    public function getBaseHealingModAttribute()
    {
        $value = $this->baseSkill->base_healing_mod_bonus_per_level;

        if (is_null($value) || ! ($value > 0.0)) {
            return 0.0;
        }

        $itemBonus = $this->getItemBonuses($this->baseSkill, 'base_healing_mod', true);

        $baseBonus = (
            $value * $this->level
        );

        $baseBonus += $this->getCharacterBoonsBonus('base_healing_mod_bonus');

        return $itemBonus + $baseBonus;
    }

    /**
     * Resolve this Skill's total base AC modifier from its base bonus and item bonuses.
     *
     * @return float
     */
    public function getBaseACModAttribute()
    {
        $value = $this->baseSkill->base_ac_mod_bonus_per_level;

        if (is_null($value) || ! ($value > 0.0)) {
            return 0.0;
        }

        $itemBonus = $this->getItemBonuses($this->baseSkill, 'base_ac_mod', true);

        $baseBonus = (
            $this->baseSkill->base_ac_mod_bonus_per_level * $this->level
        );

        $baseBonus += $this->getCharacterBoonsBonus('base_ac_mod_bonus');

        return $itemBonus + $baseBonus;
    }

    /**
     * Resolve this Skill's fight timeout modifier, capped at its maximum allowed reduction.
     *
     * @return float
     */
    public function getFightTimeOutModAttribute()
    {
        $value = $this->baseSkill->fight_time_out_mod_bonus_per_level;

        if (is_null($value) || ! ($value > 0.0)) {
            return 0.0;
        }

        $baseBonus = $this->calculateTotalTimeBonus($this, 'fight_time_out_mod_bonus_per_level');
        $itemBonus = $this->getItemBonuses($this->baseSkill, 'fight_time_out_mod_bonus', true);

        $total = $baseBonus + $itemBonus + $value;

        if ($total >= 0.50) {
            return 0.50;
        }

        return $total;
    }

    /**
     * Resolve this Skill's move timeout modifier, capped at its maximum allowed reduction.
     *
     * @return float
     */
    public function getMoveTimeOutModAttribute()
    {

        $value = $this->baseSkill->move_time_out_mod_bonus_per_level;

        if (is_null($value) || ! ($value > 0.0)) {
            return 0.0;
        }

        $itemBonus = $this->getItemBonuses($this->baseSkill, 'move_time_out_mod_bonus', true);

        $baseBonus = $this->calculateTotalTimeBonus($this, 'move_time_out_mod_bonus_per_level');

        $totalBonus = $value + $itemBonus + $baseBonus;

        if ($totalBonus > 1) {
            return 1.0;
        }

        return $totalBonus;
    }

    /**
     * Resolve this Skill's total bonus for its current level, including item, boon, and Class
     * specific training bonuses.
     *
     * @return float
     */
    public function getSkillBonusAttribute()
    {
        if (is_null($this->baseSkill->skill_bonus_per_level)) {
            return 0.0;
        }

        $level = min(max($this->level, 1), $this->baseSkill->max_level);

        $bonus = ($this->baseSkill->skill_bonus_per_level * ($level - 1));

        if ($level >= $this->baseSkill->max_level) {
            $bonus = 1.0;
        }

        $bonus += $this->getItemBonuses($this->baseSkill);

        $bonus += $this->getCharacterBoonsBonus('increase_skill_bonus_by');

        $accuracy = $this->getCharacterSkillBonus($this->character, 'Accuracy');
        $looting = $this->getCharacterSkillBonus($this->character, 'Looting');
        $dodge = $this->getCharacterSkillBonus($this->character, 'Dodge');

        switch ($this->baseSkill->name) {
            case 'Accuracy':
                $totalBonus = $bonus + $accuracy;
                break;
            case 'Looting':
                $totalBonus = $bonus + $looting;
                break;
            case 'Dodge':
                $totalBonus = $bonus + $dodge;
                break;
            default:
                $totalBonus = $bonus;
        }

        if ($totalBonus > 1.0) {
            return 1.0;
        }

        $totalBonus += $this->getClassSpecificTrainingBonus($this->character);

        return $totalBonus;
    }

    /**
     * Resolve this Skill's total training bonus from item bonuses, boons, and Class specific
     * training bonuses.
     *
     * @return float
     */
    public function getSkillTrainingBonusAttribute()
    {
        $bonus = 0.0;

        $bonus += $this->getItemBonuses($this->baseSkill, 'skill_training_bonus');
        $bonus += $this->getCharacterBoonsBonus('increase_skill_training_bonus_by');
        $bonus += $this->getClassSpecificTrainingBonus($this->character);

        return $bonus;
    }

    /**
     * Resolve the Character's Class modifier bonus for a named Skill.
     */
    private function getCharacterSkillBonus(Character $character, string $name): float
    {
        return $character->class->{Str::snake($name.'_mod')} ?? 0.0;
    }

    /**
     * Resolve the flat training bonus granted for a Class specific crafting Skill.
     *
     * @param Character $character Character whose Class-specific training bonus is being calculated.
     * @return float Calculated Class-specific training bonus.
     */
    private function getClassSpecificTrainingBonus(Character $character): float
    {
        $skillBonusSources = $this->getSkillBonusSources();
        $class = $skillBonusSources->getGameClass($character);

        if ($class->type()->isBlacksmith() && ($this->baseSkill->name === 'Weapon Crafting' || $this->baseSkill->name === 'Armour Crafting' || $this->baseSkill->name === 'Ring Crafting')) {
            return 0.15;
        }

        if ($class->type()->isArcaneAlchemist() && ($this->baseSkill->name === 'Spell Crafting' || $this->baseSkill->name === 'Alchemy')) {
            return 0.15;
        }

        return 0.0;
    }

    /**
     * Resolve the total Skill bonus contributed by equipped and, optionally, quest items.
     *
     * @param GameSkill $skill Base Game Skill whose item bonuses are being resolved.
     * @param string $skillAttribute Requested Skill attribute to sum bonuses for.
     * @param bool $equippedOnly Whether to restrict the bonus to equipped items only.
     * @return float Total Skill bonus contributed by the matching items.
     */
    private function getItemBonuses(GameSkill $skill, string $skillAttribute = 'skill_bonus', bool $equippedOnly = false): float
    {
        $skillBonusSources = $this->getSkillBonusSources();
        $bonus = 0.0;

        foreach ($skillBonusSources->getEquippedSlotsWithItems() as $slot) {
            $bonus += $this->calculateBonus($slot->item, $skill, $skillAttribute);
        }

        if (! $equippedOnly) {
            foreach ($skillBonusSources->getQuestSlotsWithItems() as $slot) {
                $bonus += $this->calculateBonus($slot->item, $this->baseSkill, $skillAttribute);
            }
        }

        return $bonus;
    }

    /**
     * Build the list of equipped/quest items contributing a positive bonus for the given Skill attribute.
     *
     * @param GameSkill $skill Base Game Skill whose contributing items are being resolved.
     * @param string $skillAttribute Requested Skill attribute to build the breakdown for.
     * @return array Item bonus breakdown entries, each describing the contributing item and its bonus amount.
     */
    private function getItemBonusBreakDown(GameSkill $skill, string $skillAttribute = 'skill_bonus'): array
    {
        $skillBonusSources = $this->getSkillBonusSources();
        $itemsThatEffectBonus = [];

        foreach ($skillBonusSources->getEquippedSlotsWithItems() as $slot) {

            $bonus = $this->calculateBonus($slot->item, $skill, $skillAttribute);

            if ($bonus > 0) {
                $itemsThatEffectBonus[] = [
                    'name' => $slot->item->affix_name,
                    'type' => $slot->item->type,
                    'position' => $slot->position,
                    'affix_count' => $slot->item->affix_count,
                    'is_unique' => $slot->item->is_unique,
                    'is_mythic' => $slot->item->is_mythic,
                    'is_cosmic' => $slot->item->is_comsmic,
                    'holy_stacks_applied' => $slot->item->holy_stacks_applied,
                    $skillAttribute => $bonus,
                ];
            }
        }

        foreach ($skillBonusSources->getQuestSlotsWithItems() as $slot) {
            if ($slot->item->type === 'quest' && $slot->item->skill_name === $this->baseSkill->name) {

                $bonus = $this->calculateBonus($slot->item, $skill, $skillAttribute);

                if ($bonus > 0) {
                    $itemsThatEffectBonus[] = [
                        'name' => $slot->item->affix_name,
                        'type' => $slot->item->type,
                        'position' => $slot->position,
                        'affix_count' => $slot->item->affix_count,
                        'is_unique' => $slot->item->is_unique,
                        'is_mythic' => $slot->item->is_mythic,
                        'is_cosmic' => $slot->item->is_comsmic,
                        'holy_stacks_applied' => $slot->item->holy_stacks_applied,
                        $skillAttribute => $bonus,
                    ];
                }
            }
        }

        return $itemsThatEffectBonus;
    }

    /**
     * Resolve the total bonus contributed by the Character's active Boons for the given attribute.
     *
     * @param string $skillBonusAttribute Requested Skill bonus attribute to sum across active Boons.
     * @return float Total bonus contributed by the Character's active Boons for the given attribute.
     */
    private function getCharacterBoonsBonus(string $skillBonusAttribute)
    {
        $skillBonusSources = $this->getSkillBonusSources();
        $newBonus = 0.0;

        foreach ($skillBonusSources->getBoonsWithItemUsed() as $boon) {
            $itemUsed = $boon->itemUsed;

            if (is_null($itemUsed)) {
                continue;
            }

            $value = $itemUsed->{$skillBonusAttribute};

            if (is_null($value)) {
                continue;
            }

            $amountUsed = $itemUsed->can_stack ? $boon->amount_used : 1;

            $newBonus += $value * $amountUsed;
        }

        return $newBonus;
    }

    /**
     * Resolve the factory used to create new Skill instances.
     *
     * @return SkillFactory Factory used to create new Skill instances.
     */
    protected static function newFactory(): SkillFactory
    {
        return SkillFactory::new();
    }

    /**
     * Resolve the Skill bonus context service for this Skill instance.
     *
     * Eloquent accessor boundary: constructor injection is not available on a Model, so this
     * narrow, documented container resolution is the permitted exception rather than a general
     * service-locator pattern.
     *
     * @return SkillBonusContextService Skill bonus context service scoped to this Skill instance.
     */
    private function getSkillBonusSources(): SkillBonusContextService
    {
        $skillBonusSources = resolve(SkillBonusContextService::class);

        $skillBonusSources->setSkillInstance($this);

        return $skillBonusSources;
    }
}
