<?php

namespace App\Flare\Models;

use App\Flare\Models\Traits\CalculateSkillBonus;
use App\Flare\Models\Traits\CalculateTimeReduction;
use App\Flare\Models\Traits\ClassBasedBonuses;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\SkillFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Skill extends Model
{

    use HasFactory, CalculateSkillBonus, CalculateTimeReduction;

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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'currently_training'    => 'boolean',
        'is_locked'             => 'boolean',
        'level'                 => 'integer',
        'xp'                    => 'integer',
        'xp_max'                => 'integer',
        'xp_towards'            => 'float',
    ];

    public function type(): SkillTypeValue {
        return $this->baseSkill->skillType();
    }

    public function baseSkill() {
        return $this->belongsTo(GameSkill::class, 'game_skill_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function getNameAttribute() {
        return $this->baseSkill->name;
    }

    public function getDescriptionAttribute() {
        return $this->baseSkill->description;
    }

    public function getMaxLevelAttribute() {
        return $this->baseSkill->max_level;
    }

    public function getCanTrainAttribute() {
        return $this->baseSkill->can_train;
    }

    public function getReducesTimeAttribute() {
        if (is_null($this->baseSkill->fight_time_out_mod_bonus_per_level)) {
            return false;
        }

        if (floatval($this->baseSkill->fight_time_out_mod_bonus_per_level) === 0.0) {
            return false;
        }

        return true;
    }

    public function getUnitTimeReductionAttribute() {
        return $this->baseSkill->unit_time_reduction * $this->level;
    }

    public function getBuildingTimeReductionAttribute() {
        return $this->baseSkill->building_time_reduction * $this->level;
    }

    public function getUnitMovementTimeReductionAttribute() {
        return $this->baseSkill->unit_movement_time_reduction * $this->level;
    }

    public function getReducesMovementTimeAttribute() {

        if (is_null($this->baseSkill->move_time_out_mod_bonus_per_level)) {
            return false;
        }

        if (floatval($this->baseSkill->move_time_out_mod_bonus_per_level) === 0.0) {
            return false;
        }

        return true;
    }

    public function getBaseDamageModAttribute() {

        $value = $this->baseSkill->base_damage_mod_bonus_per_level;

        if (is_null($value) || !($value > 0.0)) {
            return 0.0;
        }

        $itemBonus  = $this->getItemBonuses($this->baseSkill, 'base_damage_mod_bonus', true);

        $baseBonus = (
            $value * $this->level
        );

        $baseBonus += $this->getCharacterBoonsBonus('base_damage_mod_bonus');

        return $itemBonus + $baseBonus;
    }

    public function getBaseHealingModAttribute() {
        $value = $this->baseSkill->base_healing_mod_bonus_per_level;

        if (is_null($value) || !($value > 0.0)) {
            return 0.0;
        }

        $itemBonus = $this->getItemBonuses($this->baseSkill, 'base_damage_mod_bonus', true);

        $baseBonus = (
            $value * $this->level
        );

        $baseBonus += $this->getCharacterBoonsBonus('base_healing_mod_bonus');

        return $itemBonus + $baseBonus;
    }

    public function getBaseACModAttribute() {
        $value = $this->baseSkill->base_ac_mod_bonus_per_level;

        if (is_null($value) || !($value > 0.0)) {
            return 0.0;
        }

        $itemBonus = $this->getItemBonuses($this->baseSkill, 'base_ac_mod_bonus', true);

        $baseBonus = (
            $this->baseSkill->base_ac_mod_bonus_per_level * $this->level
        );

        $baseBonus += $this->getCharacterBoonsBonus( 'base_ac_mod_bonus');

        return $itemBonus + $baseBonus;
    }

    public function getFightTimeOutModAttribute() {
        $value = $this->baseSkill->fight_time_out_mod_bonus_per_level;

        if (is_null($value) || !($value > 0.0)) {
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

    public function getMoveTimeOutModAttribute() {
        $value = $this->baseSkill->move_time_out_mod_bonus_per_level;

        if (is_null($value) || !($value > 0.0)) {
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

    public function getSkillBonusAttribute() {
        if (is_null($this->character)) {
            // Monsters base skill:
            return ($this->baseSkill->skill_bonus_per_level * $this->level);
        }

        if (is_null($this->baseSkill->skill_bonus_per_level)) {
            return 0.0;
        }

        $bonus = ($this->baseSkill->skill_bonus_per_level * ($this->level - 1));
        $bonus += $this->getItemBonuses($this->baseSkill);

        $bonus += $this->getCharacterBoonsBonus('skill_bonus');

        $accuracy = $this->getCharacterSkillBonus($this->character, 'Accuracy');
        $looting  = $this->getCharacterSkillBonus($this->character, 'Looting');
        $dodge    = $this->getCharacterSkillBonus($this->character, 'Dodge');

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

        return $totalBonus;
    }

    public function getSkillTrainingBonusAttribute() {
        $bonus = 0.0;

        $bonus += $this->getItemBonuses($this->baseSkill, 'skill_training_bonus');
        $bonus += $this->getCharacterBoonsBonus('skill_training_bonus');

        return $bonus;
    }

    protected function getCharacterSkillBonus(Character $character, string $name): float {
        $raceSkillBonusValue  = $character->race->{Str::snake($name . '_mod')};
        $classSkillBonusValue = $character->class->{Str::snake($name . '_mod')};

        return $raceSkillBonusValue + $classSkillBonusValue;
    }

    protected function getItemBonuses(GameSkill $skill, string $skillAttribute = 'skill_bonus', bool $equippedOnly = false): float {
        $bonuses = [];

        forEach($this->fetchSlotsWithEquipment() as $slot) {
            $bonuses[] = $this->calculateBonus($slot->item, $skill, $skillAttribute);
        }

        if (!$equippedOnly) {
            foreach ($this->character->inventory->slots as $slot) {
                if ($slot->item->type === 'quest' && $slot->item->skill_name === $this->baseSkill->name) {
                    $bonuses[] = $this->calculateBonus($slot->item, $this->baseSkill, $skillAttribute);
                }
            }
        }

        return empty($bonuses) ? 0.0 : max($bonuses);
    }

    protected function getCharacterBoonsBonus(string $skillBonusAttribute) {
        $newBonus = 0.0;

        if ($this->character->boons->isNotEmpty()) {
            $boons = $this->character->boons;

            if ($boons->isNotEmpty()) {
                $newBonus += $boons->sum($skillBonusAttribute);
            }
        }

        return $newBonus;
    }

    private function fetchSlotsWithEquipment(): Collection  {
        $slotsEquipped = $this->character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        });

        if ($slotsEquipped->isEmpty()) {
            $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

            if (!is_null($equippedSet)) {
                return $equippedSet->slots;
            }
        }

        return $slotsEquipped;
    }

    protected static function newFactory() {
        return SkillFactory::new();
    }


}
