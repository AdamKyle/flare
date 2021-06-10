<?php

namespace App\Flare\Models;

use App\Flare\Models\Traits\CalculateSkillBonus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Bkwld\Cloner\Cloneable;
use Database\Factories\ItemFactory;
use App\Flare\Models\Traits\WithSearch;

class Item extends Model
{

    use Cloneable;

    use HasFactory, WithSearch, CalculateSkillBonus;

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
        'default_position',
        'base_damage',
        'base_ac',
        'base_healing',
        'cost',
        'base_damage_mod',
        'description',
        'base_healing_mod',
        'base_ac_mod',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'effect',
        'can_craft',
        'skill_name',
        'skill_training_bonus',
        'skill_bonus',
        'skill_level_required',
        'skill_level_trivial',
        'crafting_type',
        'market_sellable',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage'          => 'integer',
        'base_healing'         => 'integer',
        'base_ac'              => 'integer',
        'cost'                 => 'integer',
        'base_damage_mod'      => 'float',
        'base_healing_mod'     => 'float',
        'base_ac_mod'          => 'float',
        'str_mod'              => 'float',
        'dur_mod'              => 'float',
        'dex_mod'              => 'float',
        'chr_mod'              => 'float',
        'int_mod'              => 'float',
        'skill_training_bonus' => 'float',
        'skill_bonus'          => 'float',
        'can_craft'            => 'boolean',
        'skill_level_required' => 'integer',
        'skill_level_trivial'  => 'integer',
        'can_craft'            => 'boolean',
        'market_sellable'      => 'boolean',
        'skill_level_required' => 'integer',
        'skill_level_trivial'  => 'integer',
    ];

    protected $appends = [
        'affix_name',
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

    /**
     * Gets the affix name attribute.
     *
     * When calling affix_name on the item, it will return the name with all affixes applied.
     */
    public function getAffixNameAttribute() {
        if (!is_null($this->item_suffix_id) && !is_null($this->item_prefix_id)) {
            return '*' . $this->itemPrefix->name . '*' . ' ' . $this->name . ' ' .  '*' . $this->itemSuffix->name . '*';
        } else if (!is_null($this->item_suffix_id)) {
            return $this->name . ' ' .  '*' . $this->itemSuffix->name . '*';
        } else if (!is_null($this->item_prefix_id)) {
            return '*' . $this->itemPrefix->name . '*' . ' ' . $this->name;
        }

        return $this->name;
    }

    /**
     * Gets the total damage value for the item.
     *
     * In some cases an item might not have a base_damage value.
     * how ever might have either prefix or suffix or both.
     *
     * In this case we will set the damage variable to one.
     * this will allow the damage modifiers to be applied to the item.
     *
     * Which in turns allows the player to their total damage increased when
     * attacking.
     *
     * @return int.
     */
    public function scopeGetTotalDamage(): int {
        $baseDamage = is_null($this->base_damage) ? 0 : $this->base_damage;
        $damage     = $baseDamage;

        if (!is_null($this->itemPrefix)) {
            if ($damage === 0 && !is_null($this->itemPrefix->base_damage_mod)) {
                $damage = 1;
            }

            $damage = ($damage * (1 + $this->itemPrefix->base_damage_mod));
        }

        if (!is_null($this->itemSuffix)) {
            if ($damage === 0 && !is_null($this->itemSuffix->base_damage_mod)) {
                $damage = 1;
            }

            $damage = ($damage * (1 + $this->itemSuffix->base_damage_mod));
        }

        return ceil($damage);
    }

    /**
     * Gets the total defence value for the item.
     *
     * In some cases an item might not have a base_ac value.
     * how ever might have either prefix or suffix or both.
     *
     * In this case we will set the ac variable to one.
     * this will allow the ac modifiers to be applied to the item.
     *
     * Which in turns allows the player to their total ac increased when
     * defending from attacks.
     *
     * @return int.
     */
    public function scopeGetTotalDefence(): int {
        $baseAc = is_null($this->base_ac) ? 0 : $this->base_ac;
        $ac     = $baseAc;

        if (!is_null($this->itemPrefix)) {
            if ($ac === 0 && !is_null($this->itemPrefix->base_ac_mod)) {
                $ac = 1;
            }

            $ac = ($ac * (1 + $this->itemPrefix->base_ac_mod));
        }

        if (!is_null($this->itemSuffix)) {
            if ($ac === 0 && !is_null($this->itemSuffix->base_ac_mod)) {
                $ac = 1;
            }

            $ac = ($ac * (1 + $this->itemSuffix->base_ac_mod));
        }

        return ceil($ac);
    }

    /**
     * Gets the total healing value for the item.
     *
     * In some cases an item might not have a base_healing value.
     * how ever might have either prefix or suffix or both.
     *
     * In this case we will set the healFor variable to one.
     * this will allow the healing modifiers to be applied to the item.
     *
     * Which in turns allows the player to their total healing increased when
     * attacking.
     *
     * @return int.
     */
    public function scopeGetTotalHealing(): int {
        $baseHealing = is_null($this->base_healing) ? 0 : $this->base_healing;
        $healFor     = $baseHealing;

        if (!is_null($this->itemPrefix)) {
            if ($healFor === 0 && !is_null($this->itemPrefix->base_healing_mod)) {
                $healFor = 1;
            }

            $healFor = ($healFor * (1 + $this->itemPrefix->base_healing_mod));
        }

        if (!is_null($this->itemSuffix)) {
            if ($healFor === 0 && !is_null($this->itemSuffix->base_healing_mod)) {
                $healFor = 1;
            }

            $healFor = ($healFor * (1 + $this->itemSuffix->base_healing_mod));
        }

        return ceil($healFor);
    }

    /**
     * Gets the total percentage increase for a stat.
     *
     * @return float
     */
    public function getTotalPercentageForStat(string $stat): float {
        $baseStat = is_null($this->{$stat . '_mod'}) ? 0.0 : $this->{$stat . '_mod'};

        if (!is_null($this->itemPrefix)) {
            $statBonus  = $this->itemPrefix->{$stat . '_mod'};
            $baseStat  += !is_null($statBonus) ? $statBonus : 0.0;
        }

        if (!is_null($this->itemSuffix)) {
            $statBonus = $this->itemSuffix->{$stat . '_mod'};
            $baseStat += !is_null($statBonus) ? $statBonus : 0.0;
        }

        return $baseStat;
    }

    /**
     * Gets the total skill training bonus (XP bonus)
     *
     * @param $query
     * @param string $skillName
     * @return float
     */
    public function scopeGetSkillTrainingBonus($query, string $skillName): float {
        return $this->calculateTrainingBonus($this, $skillName);
    }

    public function scopeGetItemSkills($query): array {
        $skills = [];

        if (!is_null($this->itemPrefix)) {
            if (!is_null($this->itemPrefix->skill_name)) {
                $skills[] = [
                    'skill_name'           => $this->itemPrefix->skill_name,
                    'skill_training_bonus' => $this->itemPrefix->skill_training_bonus,
                    'skill_bonus'          => $this->itemPrefix->skill_bonus,
                ];
            }
        }

        if (!is_null($this->itemSuffix)) {
            if (!is_null($this->itemSuffix->skill_name)) {
                $skills[] = [
                    'skill_name'           => $this->itemSuffix->skill_name,
                    'skill_training_bonus' => $this->itemSuffix->skill_training_bonus,
                    'skill_bonus'          => $this->itemSuffix->skill_bonus,
                ];
            }
        }

        if (!is_null($this->skill_name)) {
            $skills[] = [
                'skill_name'           => $this->skill_name,
                'skill_training_bonus' => $this->skill_training_bonus,
                'skill_bonus'          => $this->skill_bonus,
            ];
        }

        return $skills;
    }

    /**
     * Gets the total skill training bonus (Bonus when using)
     *
     * @param $query
     * @param string $skillName
     * @return float
     */
    public function scopeGetSkillBonus($query, string $skillName): float {
        return $this->calculateBonus($this, $skillName);
    }

    protected static function newFactory() {
        return ItemFactory::new();
    }
}
