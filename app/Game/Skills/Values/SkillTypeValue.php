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

    const ALCHEMY                         = 4;

    const EFFECTS_BATTLE_TIMER            = 5;

    const EFFECTS_DIRECTIONAL_MOVE_TIMER  = 6;

    const EFFECTS_MINUTE_MOVE_TIMER       = 7;

    const EFFECTS_KINGDOM_BUILDING_TIMERS = 8;

    const EFFECTS_UNIT_RECRUITMENT_TIMER  = 9;

    const EFFECTS_UNIT_MOVEMENT_TIMER     = 10;

    const EFFECTS_SPELL_EVASION           = 11;

    const EFFECTS_ARTIFACT_ANNULMENT      = 12;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::TRAINING                        => 0,
        self::CRAFTING                        => 1,
        self::ENCHANTING                      => 2,
        self::DISENCHANTING                   => 3,
        self::ALCHEMY                         => 4,
        self::EFFECTS_BATTLE_TIMER            => 5,
        self::EFFECTS_DIRECTIONAL_MOVE_TIMER  => 6,
        self::EFFECTS_MINUTE_MOVE_TIMER       => 7,
        self::EFFECTS_KINGDOM_BUILDING_TIMERS => 8,
        self::EFFECTS_UNIT_RECRUITMENT_TIMER  => 9,
        self::EFFECTS_UNIT_MOVEMENT_TIMER     => 10,
        self::EFFECTS_SPELL_EVASION           => 11,
        self::EFFECTS_ARTIFACT_ANNULMENT      => 12,

    ];

    public static $namedValues = [
        0  => 'Training',
        1  => 'Crafting',
        2  => 'Enchanting',
        3  => 'Disenchanting',
        4  => 'Alchemy',
        5  => 'Effects Battle Timer',
        6  => 'Effects Directional Move Timer',
        7  => 'Effects Minute Based Movement Timer',
        8  => 'Effects Kingdom Building Timers',
        9  => 'Effects Unit Recruitment Timers',
        10 => 'Effects Unit Movement Timers',
        11 => 'Effects Spell Evasion',
        12 => 'Effects Artifact Annulment',

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
     * is disenchanting
     *
     * @return bool
     */
    public function isDisenchanting(): bool {
        return $this->value === self::DISENCHANTING;
    }

    /**
     * is alchemy
     *
     * @return bool
     */
    public function isAlchemy(): bool {
        return $this->value === self::ALCHEMY;
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
     * Does this effect the spell evasion?
     *
     * @return bool
     */
    public function isSpellEvasion(): bool {
        return $this->value === self::EFFECTS_SPELL_EVASION;
    }

    /**
     * Does this effect the artifact annulment?
     *
     * @return bool
     */
    public function isArtifactAnnulment(): bool {
        return $this->value === self::EFFECTS_ARTIFACT_ANNULMENT;
    }

    /**
     * See if the name exists in a named value.
     *
     * If it does return it, if not throw an exception.
     *
     * @return string
     */
    public function getNamedValue(): string {
        return self::$namedValues[$this->value];
    }
}
