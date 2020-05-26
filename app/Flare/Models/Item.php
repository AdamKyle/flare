<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\ArtifactProperty;
use App\Flare\Models\ItemAffix;

class Item extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'item_suffix_id',
        'item_prefix_id',
        'type',
        'base_damage',
        'cost',
        'base_damage_mod',
        'description',
        'base_healing_mod',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'ac_mod',
        'skill_name',
        'skill_training_bonus',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage'          => 'integer',
        'base_healing'         => 'integer',
        'cost'                 => 'integer',
        'base_damage_mod'      => 'float',
        'base_healing_mod'     => 'float',
        'str_mod'              => 'float',
        'dur_mod'              => 'float',
        'dex_mod'              => 'float',
        'chr_mod'              => 'float',
        'int_mod'              => 'float',
        'ac_mod'               => 'float',
        'skill_training_bonus' => 'float',
    ];

    public function itemSuffix() {
        return $this->hasOne(ItemAffix::class, 'id', 'item_suffix_id');
    }

    public function itemPrefix() {
        return $this->hasOne(ItemAffix::class, 'id', 'item_prefix_id');
    }

    public function slot() {
        return $this->belongsTo(InventorySlot::class, 'id', 'item_id');
    }

    public function scopeGetTotalDamage(): int {
        $baseDamage = $this->base_damage;
        $damage     = $baseDamage;

        if (!is_null($this->itemPrefix)) {
            $damage += ($baseDamage * $this->itemPrefix->base_damage_mod);
        }

        if (!is_null($this->itemSuffix)) {
            $damage += ($baseDamage * $this->itemSuffix->base_damage_mod);
        }

        return round($damage);
    }
}
