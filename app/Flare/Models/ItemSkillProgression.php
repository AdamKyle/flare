<?php

namespace App\Flare\Models;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Model;

class ItemSkillProgression extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'item_skill_id',
        'current_level',
        'current_kill',
        'is_training',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_level' => 'integer',
        'current_kill'  => 'integer',
        'is_training'   => 'boolean',
    ];

    protected $appends = [
        'str_mod',
        'int_mod',
        'dex_mod',
        'focus_mod',
        'chr_mod',
        'dur_mod',
        'agi_mod',
        'base_attack_mod',
        'base_ac_mod',
        'base_healing_mod',
        
    ];

    public function getStrModAttribute() {
        return $this->getModifierBonus('str');
    }

    public function getDexModAttribute() {
        return $this->getModifierBonus('dex');
    }

    public function getAgiModAttribute() {
        return $this->getModifierBonus('agi');
    }

    public function getDurModAttribute() {
        return $this->getModifierBonus('dur');
    }

    public function getIntModAttribute() {
        return $this->getModifierBonus('int');
    }

    public function getFocusModAttribute() {
        return $this->getModifierBonus('focus');
    }

    public function getChrModAttribute() {
        return $this->getModifierBonus('chr');
    }

    public function getBaseAttackModAttribute() {
        return $this->getModifierBonus('base_attack');
    }

    public function getBaseAcModAttribute() {
        return $this->getModifierBonus('base_ac');
    }

    public function getBaseHealingModAttribute() {
        return $this->getModifierBonus('base_healing');
    }

    public function itemSkill() {
        return $this->hasOne(ItemSkill::class, 'id', 'item_skill_id');
    }

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }

    protected function getModifierBonus(string $stat): float {
        return $this->itemSkill->{$stat . '_mod'} * $this->current_level;
    }
}
