<?php

namespace App\Game\Skills\Values;

enum SkillTypeValue: int
{
    /**
     * @var int $TRAINING
     */
    case TRAINING = 0;

    /**
     * @var int $CRAFTING
     */
    case CRAFTING = 1;

    /**
     * @var int $ENCHANTING
     */
    case ENCHANTING = 2;

    /**
     * @var int $DISENCHANTING
     */
    case DISENCHANTING = 3;

    /**
     * @var int $ALCHEMY
     */
    case ALCHEMY = 4;

    /**
     * @var int $EFFECTS_BATTLE_TIMER
     */
    case EFFECTS_BATTLE_TIMER = 5;

    /**
     * @var int $EFFECTS_DIRECTIONAL_MOVE_TIMER
     */
    case EFFECTS_DIRECTIONAL_MOVE_TIMER = 6;

    /**
     * @var int $EFFECTS_MOVEMENT_TIMER
     */
    case EFFECTS_MOVEMENT_TIMER = 7;

    /**
     * @var int $EFFECTS_KINGDOM_BUILDING_TIMERS
     */
    case EFFECTS_KINGDOM_BUILDING_TIMERS = 8;

    /**
     * @var int $EFFECTS_UNIT_RECRUITMENT_TIMER
     */
    case EFFECTS_UNIT_RECRUITMENT_TIMER = 9;

    /**
     * @var int $EFFECTS_UNIT_MOVEMENT_TIMER
     */
    case EFFECTS_UNIT_MOVEMENT_TIMER = 10;

    /**
     * @var int $EFFECTS_SPELL_EVASION
     */
    case EFFECTS_SPELL_EVASION = 11;

    /**
     * @var int $EFFECTS_KINGDOM
     */
    case EFFECTS_KINGDOM = 12;

    /**
     * @var int $EFFECTS_CLASS
     */
    case EFFECTS_CLASS = 13;

    /**
     * @var int $GEM_CRAFTING
     */
    case GEM_CRAFTING = 14;

    /**
     * Returns whether this skill type is Training.
     *
     * @return bool
     */
    public function isTraining(): bool
    {
        return $this === self::TRAINING;
    }

    /**
     * Returns whether this skill type is Crafting.
     *
     * @return bool
     */
    public function isCrafting(): bool
    {
        return $this === self::CRAFTING;
    }

    /**
     * Returns whether this skill type is Enchanting.
     *
     * @return bool
     */
    public function isEnchanting(): bool
    {
        return $this === self::ENCHANTING;
    }

    /**
     * Returns whether this skill type is Disenchanting.
     *
     * @return bool
     */
    public function isDisenchanting(): bool
    {
        return $this === self::DISENCHANTING;
    }

    /**
     * Returns whether this skill type is Alchemy.
     *
     * @return bool
     */
    public function isAlchemy(): bool
    {
        return $this === self::ALCHEMY;
    }

    /**
     * Returns whether this skill type is Battle Timer.
     *
     * @return bool
     */
    public function isBattleTimer(): bool
    {
        return $this === self::EFFECTS_BATTLE_TIMER;
    }

    /**
     * Returns whether this skill type is Directional Movement Timer.
     *
     * @return bool
     */
    public function isDirectionalMovementTimer(): bool
    {
        return $this === self::EFFECTS_DIRECTIONAL_MOVE_TIMER;
    }

    /**
     * Returns whether this skill type is Movement Timer.
     *
     * @return bool
     */
    public function isMovementTimer(): bool
    {
        return $this === self::EFFECTS_MOVEMENT_TIMER;
    }

    /**
     * Returns whether this skill type is Kingdom Building Timer.
     *
     * @return bool
     */
    public function isKingdomBuildingTimer(): bool
    {
        return $this === self::EFFECTS_KINGDOM_BUILDING_TIMERS;
    }

    /**
     * Returns whether this skill type is Unit Recruitment Timer.
     *
     * @return bool
     */
    public function isUnitRecruitmentTimer(): bool
    {
        return $this === self::EFFECTS_UNIT_RECRUITMENT_TIMER;
    }

    /**
     * Returns whether this skill type is Unit Movement Timer.
     *
     * @return bool
     */
    public function isUnitMovementTimer(): bool
    {
        return $this === self::EFFECTS_UNIT_MOVEMENT_TIMER;
    }

    /**
     * Returns whether this skill type affects Spell Evasion.
     *
     * @return bool
     */
    public function isSpellEvasion(): bool
    {
        return $this === self::EFFECTS_SPELL_EVASION;
    }

    /**
     * Returns whether this skill type is Gem Crafting.
     *
     * @return bool
     */
    public function isGemCrafting(): bool
    {
        return $this === self::GEM_CRAFTING;
    }

    /**
     * Returns whether this skill type affects Kingdom.
     *
     * @return bool
     */
    public function effectsKingdom(): bool
    {
        return $this === self::EFFECTS_KINGDOM;
    }

    /**
     * Returns whether this skill type affects Class Skills.
     *
     * @return bool
     */
    public function effectsClassSkills(): bool
    {
        dump($this, self::EFFECTS_CLASS);
        return $this === self::EFFECTS_CLASS;
    }

    /**
     * Returns the named value associated with this skill type.
     *
     * @return string
     */
    public function getNamedValue(): string
    {
        return self::NAMED_VALUES[$this->value];
    }

    /**
     * Returns an array of all skill type named values.
     *
     * @return array
     */
    public static function getValues(): array
    {
        return self::NAMED_VALUES;
    }

    /**
     * @var array $NAMED_VALUES
     */
    private const NAMED_VALUES = [
        0  => 'Training',
        1  => 'Crafting',
        2  => 'Enchanting',
        3  => 'Disenchanting',
        4  => 'Alchemy',
        5  => 'Effects Battle Timer',
        6  => 'Effects Directional Move Timer',
        7  => 'Effects Movement Timer',
        8  => 'Effects Kingdom Building Timers',
        9  => 'Effects Unit Recruitment Timers',
        10 => 'Effects Unit Movement Timers',
        11 => 'Effects Spell Evasion',
        12 => 'Effects Kingdoms',
        13 => 'Effects Class',
        14 => 'Gem Crafting',
    ];
}
