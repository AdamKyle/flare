<?php

namespace App\Game\Skills\Values;

class SkillTypeValue {

    /**
     * @var string $value
     */
    private $value;

    const TRAINING                        = 0;

    const CRAFTING                        = 1;

    const ENCHANTING                      = 2;

    const DISENCHANTING                   = 3;

    const EFFECTS_BATTLE_TIMER            = 4;

    const EFFECTS_DIRECTIONAL_MOVE_TIMER  = 5;

    const EFFECTS_MINUTE_MOVE_TIMER       = 6;

    const EFFECTS_KINGDOM_BUILDING_TIMERS = 7;

    const EFFECTS_UNIT_RECRUITMENT_TIMER  = 8;

    const EFFECTS_UNIT_MOVEMENT_TIMER     = 9;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::TRAINING                        => 0,
        self::CRAFTING                        => 1,
        self::ENCHANTING                      => 2,
        self::DISENCHANTING                   => 3,
        self::EFFECTS_BATTLE_TIMER            => 4,
        self::EFFECTS_DIRECTIONAL_MOVE_TIMER  => 5,
        self::EFFECTS_MINUTE_MOVE_TIMER       => 6,
        self::EFFECTS_KINGDOM_BUILDING_TIMERS => 7,
        self::EFFECTS_UNIT_RECRUITMENT_TIMER  => 8,
        self::EFFECTS_UNIT_MOVEMENT_TIMER     => 9
    ];

    protected static $namedValues = [
        0 => 'Training',
        1 => 'Crafting',
        2 => 'Enchanting',
        3 => 'Disenchanting',
        4 => 'Effects Battle Timer',
        5 => 'Effects Directional Move Timer',
        6 => 'Effects Minute Based Movement Timer',
        7 => 'Effects Kingdom Building Timers',
        8 => 'Effects Unit Recruitment Timers',
        9 => 'Effects Unit Movement Timers',
    ];

    /**
     * NpcTypes constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * is training.
     *
     * @return bool
     */
    public function isTraining(): bool {
        return $this->value === self::TRAINING;
    }

    /**
     * is crafting
     *
     * @return bool
     */
    public function isCrafting(): bool {
        return $this->value === self::CRAFTING;
    }

    /**
     * is enchanting
     *
     * @return bool
     */
    public function isEnchanting(): bool {
        return $this->value === self::ENCHANTING;
    }

    /**
     * is  disenchanting
     *
     * @return bool
     */
    public function isDisenchanting(): bool {
        return $this->value === self::DISENCHANTING;
    }

    /**
     * Is battle timer?
     *
     * @return bool
     */
    public function isBattleTimer(): bool {
        return $this->value === self::EFFECTS_BATTLE_TIMER;
    }

    /**
     * Is directional movement timer?
     *
     * @return bool
     */
    public function isDirectionalMovementTimer(): bool {
        return $this->value === self::EFFECTS_DIRECTIONAL_MOVE_TIMER;
    }

    /**
     * Is minute movement timer?
     *
     * @return bool
     */
    public function isMinuteMovementTimer(): bool {
        return $this->value === self::EFFECTS_MINUTE_MOVE_TIMER;
    }

    /**
     * Is kingdom building timer?
     *
     * @return bool
     */
    public function isKingdomBuildingTimer(): bool {
        return $this->value === self::EFFECTS_KINGDOM_BUILDING_TIMERS;
    }

    /**
     * Is unit recruitment timer?
     *
     * @return bool
     */
    public function isUnitRecruitmentTimer(): bool {
        return $this->value === self::EFFECTS_UNIT_RECRUITMENT_TIMER;
    }

    /**
     * is unit movement timer?
     *
     * @return bool
     */
    public function isUnitMovementTimer(): bool {
        return $this->value === self::EFFECTS_UNIT_MOVEMENT_TIMER;
    }

    /**
     * See if the name exists in a named value.
     *
     * If it does return it, if not throw an exception.
     *
     * @return string
     * @throws Exception
     */
    public function getNamedValue(): string {
        if (isset(self::$namedValues[$this->value])) {
            return self::$namedValues[$this->value];
        }

        throw new Exception($this->value . ' does not exist for named value');
    }
}
