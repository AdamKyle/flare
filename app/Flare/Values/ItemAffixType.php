<?php

namespace App\Flare\Values;

use Exception;
use Illuminate\Database\Eloquent\Builder;

class ItemAffixType
{
    /**
     * @var string
     */
    private $value;

    const STAT_MODIFIERS = 0;

    const BASE_MODIFIERS = 1;

    const STAT_REDUCTION = 2;

    const SKILL_REDUCTION = 3;

    const LIFE_STEALING = 4;

    const DAMAGE_STACKING = 5;

    const DAMAGE_IRRESISTIBLE = 6;

    const ACCURACY = 7;

    const CASTING_ACCURACY = 8;

    const DODGE = 9;

    const CRITICALITY = 10;

    const LOOTING = 11;

    const WEAPON_CRAFTING = 12;

    const ARMOUR_CRAFTING = 13;

    const RING_CRAFTING = 14;

    const SPELL_CRAFTING = 15;

    const ENCHANTMENT_CRAFTING = 16;

    const ENTRANCING = 17;

    const RANDOMLY_GENERATED = 18;

    /**
     * @var string[]
     */
    public static $values = [
        self::STAT_MODIFIERS => self::STAT_MODIFIERS,
        self::BASE_MODIFIERS => self::BASE_MODIFIERS,
        self::STAT_REDUCTION => self::STAT_REDUCTION,
        self::SKILL_REDUCTION => self::SKILL_REDUCTION,
        self::LIFE_STEALING => self::LIFE_STEALING,
        self::DAMAGE_STACKING => self::DAMAGE_STACKING,
        self::DAMAGE_IRRESISTIBLE => self::DAMAGE_IRRESISTIBLE,
        self::ACCURACY => self::ACCURACY,
        self::CASTING_ACCURACY => self::CASTING_ACCURACY,
        self::DODGE => self::DODGE,
        self::CRITICALITY => self::CRITICALITY,
        self::LOOTING => self::LOOTING,
        self::WEAPON_CRAFTING => self::WEAPON_CRAFTING,
        self::ARMOUR_CRAFTING => self::ARMOUR_CRAFTING,
        self::RING_CRAFTING => self::RING_CRAFTING,
        self::SPELL_CRAFTING => self::SPELL_CRAFTING,
        self::ENCHANTMENT_CRAFTING => self::ENCHANTMENT_CRAFTING,
        self::ENTRANCING => self::ENTRANCING,
        self::RANDOMLY_GENERATED => self::RANDOMLY_GENERATED,
    ];

    /**
     * For the affixes live-wire table.
     *
     * @var string[]
     */
    public static $dropDownValues = [
        self::STAT_MODIFIERS => 'Stat Modifiers',
        self::BASE_MODIFIERS => 'Base Modifiers',
        self::STAT_REDUCTION => 'Stat Reduction',
        self::SKILL_REDUCTION => 'Skill/Res Reduction + Devouring Light',
        self::LIFE_STEALING => 'Life stealing',
        self::DAMAGE_STACKING => 'Stacking Damage',
        self::DAMAGE_IRRESISTIBLE => 'Irresistible Damage',
        self::ACCURACY => 'Accuracy',
        self::CASTING_ACCURACY => 'Casting Accuracy',
        self::DODGE => 'Dodge',
        self::CRITICALITY => 'Criticality',
        self::LOOTING => 'Looting',
        self::WEAPON_CRAFTING => 'Weapon Crafting',
        self::ARMOUR_CRAFTING => 'Armour Crafting',
        self::RING_CRAFTING => 'Ring Crafting',
        self::SPELL_CRAFTING => 'Spell Crafting',
        self::ENCHANTMENT_CRAFTING => 'Enchantment Crafting',
        self::ENTRANCING => 'Entrancing',
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws \Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist on ItemAffixType');
        }

        $this->value = $value;
    }

    /**
     * convert name to type.
     *
     * @throws Exception
     */
    public static function convertNameToType(string $name): int
    {
        if (in_array($name, self::$dropDownValues)) {
            $value = array_search($name, self::$dropDownValues);

            if (! $value) {
                throw new Exception($name . ' not found for ItemAffixType');
            }

            return $value;
        }

        throw new Exception($name . ' not found for ItemAffixType');
    }

    /**
     * Query by type.
     */
    public function query(Builder $query): Builder
    {
        return $query->where('affix_type', $this->value);
    }
}
