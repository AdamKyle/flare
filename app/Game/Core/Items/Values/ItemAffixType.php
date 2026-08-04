<?php

namespace App\Game\Core\Items\Values;

use Exception;
use Illuminate\Database\Eloquent\Builder;

enum ItemAffixType: int
{
    case STAT_MODIFIERS = 0;
    case BASE_MODIFIERS = 1;
    case STAT_REDUCTION = 2;
    case SKILL_REDUCTION = 3;
    case LIFE_STEALING = 4;
    case DAMAGE_STACKING = 5;
    case DAMAGE_IRRESISTIBLE = 6;
    case ACCURACY = 7;
    case CASTING_ACCURACY = 8;
    case DODGE = 9;
    case CRITICALITY = 10;
    case LOOTING = 11;
    case WEAPON_CRAFTING = 12;
    case ARMOUR_CRAFTING = 13;
    case RING_CRAFTING = 14;
    case SPELL_CRAFTING = 15;
    case ENCHANTMENT_CRAFTING = 16;
    case ENTRANCING = 17;
    case RANDOMLY_GENERATED = 18;

    public static function fromValue(int $value): self
    {
        $type = self::tryFrom($value);

        if (is_null($type)) {
            throw new Exception($value.' does not exist on ItemAffixType');
        }

        return $type;
    }

    public static function dropDownValues(): array
    {
        return [
            self::STAT_MODIFIERS->value => 'Stat Modifiers',
            self::BASE_MODIFIERS->value => 'Base Modifiers',
            self::STAT_REDUCTION->value => 'Stat Reduction',
            self::SKILL_REDUCTION->value => 'Skill/Res Reduction + Devouring Light',
            self::LIFE_STEALING->value => 'Life stealing',
            self::DAMAGE_STACKING->value => 'Stacking Damage',
            self::DAMAGE_IRRESISTIBLE->value => 'Irresistible Damage',
            self::ACCURACY->value => 'Accuracy',
            self::CASTING_ACCURACY->value => 'Casting Accuracy',
            self::DODGE->value => 'Dodge',
            self::CRITICALITY->value => 'Criticality',
            self::LOOTING->value => 'Looting',
            self::WEAPON_CRAFTING->value => 'Weapon Crafting',
            self::ARMOUR_CRAFTING->value => 'Armour Crafting',
            self::RING_CRAFTING->value => 'Ring Crafting',
            self::SPELL_CRAFTING->value => 'Spell Crafting',
            self::ENCHANTMENT_CRAFTING->value => 'Enchantment Crafting',
            self::ENTRANCING->value => 'Entrancing',
        ];
    }

    public static function convertNameToType(string $name): int
    {
        $value = array_search($name, self::dropDownValues(), true);

        if ($value === false || $value === self::STAT_MODIFIERS->value) {
            throw new Exception($name.' not found for ItemAffixType');
        }

        return $value;
    }

    public function query(Builder $query): Builder
    {
        return $query->where('affix_type', $this->value);
    }
}
