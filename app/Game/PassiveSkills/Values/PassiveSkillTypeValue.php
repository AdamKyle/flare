<?php

namespace App\Game\PassiveSkills\Values;

class PassiveSkillTypeValue {

    /**
     * @var string $value
     */
    private $value;

    const KINGDOM_DEFENCE                 = 0;
    const KINGDOM_RESOURCE_GAIN           = 1;
    const KINGDOM_UNIT_COST_REDUCTION     = 2;
    const KINGDOM_BUILDING_COST_REDUCTION = 3;
    const BUILDING_BLUE_PRINTS            = 4;


    /**
     * @var string[] $values
     */
    protected static $values = [
        self::KINGDOM_DEFENCE                 => 0,
        self::KINGDOM_RESOURCE_GAIN           => 1,
        self::KINGDOM_UNIT_COST_REDUCTION     => 2,
        self::KINGDOM_BUILDING_COST_REDUCTION => 3,
        self::BUILDING_BLUE_PRINTS            => 4,
    ];

    /**
     * @var string[] $namedValues
     */
    public static $namedValues = [
        0  => 'Kingdom Defence',
        1  => 'Kingdom Resource Gain',
        2  => 'Kingdom Unit Cost Reduction',
        3  => 'Kingdom Building Cost Reduction',
        4  => 'Building Blue Prints'

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

    public function isDefence(): bool {
        return $this->value === self::KINGDOM_DEFENCE;
    }

    public function isResourceGain(): bool {
        return $this->value === self::KINGDOM_RESOURCE_GAIN;
    }

    public function isUnitCostReduction(): bool {
        return $this->value === self::KINGDOM_UNIT_COST_REDUCTION;
    }

    public function isBuildingBluePrints(): bool {
        return $this->value === self::BUILDING_BLUE_PRINTS;
    }

    /**
     * is disenchanting
     *
     * @return bool
     */
    public function isBuildingCostReduction(): bool {
        return $this->value === self::KINGDOM_BUILDING_COST_REDUCTION;
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

    /**
     * @return string[]
     */
    public static function getNamedValues(): array {
        return self::$namedValues;
    }
}
