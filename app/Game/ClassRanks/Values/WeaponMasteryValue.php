<?php

namespace App\Game\ClassRanks\Values;

use Exception;

class WeaponMasteryValue
{
    const WEAPON = 0;

    const BOW = 1;

    const HAMMER = 2;

    const STAVE = 3;

    const DAMAGE_SPELL = 4;

    const HEALING_SPELL = 5;

    const GUN = 6;

    const FAN = 7;

    const MACE = 8;

    const SCRATCH_AWL = 9;

    const CLAWN = 10;

    const CENSOR = 11;

    const WAND = 12;

    const SWORD = 13;

    const XP_PER_LEVEL = 1000000;

    const XP_PER_KILL = 10000;

    const MAX_LEVEL = 100;

    protected static $values = [
        self::WEAPON => 0,
        self::BOW => 1,
        self::HAMMER => 2,
        self::STAVE => 3,
        self::DAMAGE_SPELL => 4,
        self::HEALING_SPELL => 5,
        self::GUN => 6,
        self::FAN => 7,
        self::MACE => 8,
        self::SCRATCH_AWL => 9,
        self::CLAWN => 10,
        self::CENSOR => 11,
        self::WAND => 12,
        self::SWORD => 13,
    ];

    protected static $attributes = [
        self::WEAPON => 'Weapons',
        self::BOW => 'Bows',
        self::GUN => 'Guns',
        self::FAN => 'Fans',
        self::MACE => 'Maces',
        self::SCRATCH_AWL => 'scratch-awl',
        self::HAMMER => 'Hammers',
        self::STAVE => 'Staves',
        self::DAMAGE_SPELL => 'Damage Spells',
        self::HEALING_SPELL => 'Healing Spells',
    ];

    private int $value;

    /**
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * @return int[]
     */
    public static function getTypes(): array
    {
        return self::$values;
    }

    public function getName()
    {
        return self::$attributes[$this->value];
    }

    /**
     * Get numeric value for item type:
     *
     * @throws Exception
     */
    public static function getNumericValueForStringType(string $type): int
    {
        $type = strtolower($type);

        switch ($type) {
            case 'weapon':
                return self::WEAPON;
            case 'hammer':
                return self::HAMMER;
            case 'stave':
                return self::STAVE;
            case 'bow':
                return self::BOW;
            case 'gun':
                return self::GUN;
            case 'fan':
                return self::FAN;
            case 'mace':
                return self::MACE;
            case 'scratch-awl':
                return self::SCRATCH_AWL;
            case 'claw':
                return self::CLAWN;
            case 'censor':
                return self::CENSOR;
            case 'wand':
                return self::WAND;
            case 'sword':
                return self::SWORD;
            case 'spell-damage':
                return self::DAMAGE_SPELL;
            case 'spell-healing':
                return self::HEALING_SPELL;
            default:
                throw new Exception('Undefined type for: '.$type);
        }
    }

    public static function getTypeForNumericalValue(int $type): string
    {
        switch ($type) {
            case self::WEAPON:
                return 'weapon';
            case self::HAMMER:
                return 'hammer';
            case self::STAVE:
                return 'stave';
            case self::BOW:
                return 'bow';
            case self::GUN:
                return 'gun';
            case self::FAN:
                return 'fan';
            case self::MACE:
                return 'mace';
            case self::SCRATCH_AWL:
                return 'scratch-awl';
            case self::CLAWN:
                return 'claw';
            case self::CENSOR:
                return 'censor';
            case self::WAND:
                return 'wand';
            case self::SWORD:
                return 'sword';
            case self::DAMAGE_SPELL:
                return 'spell-damage';
            case self::HEALING_SPELL:
                return 'spell-healing';
            default:
                throw new Exception('Undefined type for: '.$type);
        }
    }

    /**
     * is valid type?
     */
    public static function isValidType(string $type): bool
    {
        $types = ['weapon', 'hammer', 'bow', 'gun', 'fan', 'mace', 'scratch-awl', 'stave', 'sword', 'claw', 'censor', 'wand', 'spell-damage', 'spell-healing'];

        return in_array(strtolower($type), $types);
    }

    public function getAttribute(): string
    {
        return self::$attributes[$this->value];
    }

    public function isStaff(): bool
    {
        return $this->value === self::STAVE;
    }

    public function isWeapon(): bool
    {
        return $this->value === self::WEAPON;
    }

    public function isHammer(): bool
    {
        return $this->value === self::HAMMER;
    }

    public function isBow(): bool
    {
        return $this->value === self::BOW;
    }

    public function isGun(): bool
    {
        return $this->value === self::GUN;
    }

    public function isFan(): bool
    {
        return $this->value === self::FAN;
    }

    public function isMace(): bool
    {
        return $this->value === self::MACE;
    }

    public function isScratchAwl(): bool
    {
        return $this->value === self::SCRATCH_AWL;
    }

    public function isClaw(): bool
    {
        return $this->value === self::CLAWN;
    }

    public function isCensor(): bool
    {
        return $this->value === self::CENSOR;
    }

    public function isWand(): bool
    {
        return $this->value === self::WAND;
    }

    public function isSword(): bool
    {
        return $this->value === self::SWORD;
    }

    public function isDamageSpell(): bool
    {
        return $this->value === self::DAMAGE_SPELL;
    }

    public function isHealingSpell(): bool
    {
        return $this->value === self::HEALING_SPELL;
    }
}
