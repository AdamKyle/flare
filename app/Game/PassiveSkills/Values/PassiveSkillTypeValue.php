<?php

namespace App\Game\PassiveSkills\Values;

use Exception;

class PassiveSkillTypeValue {

    /**
     * @var string $value
     */
    private $value;

    const KINGDOM_DEFENCE                 = 0;
    const KINGDOM_RESOURCE_GAIN           = 1;
    const KINGDOM_UNIT_COST_REDUCTION     = 2;
    const KINGDOM_BUILDING_COST_REDUCTION = 3;
    const UNLOCKS_BUILDING                = 4;
    const IRON_COST_REDUCTION             = 5;
    const POPULATION_COST_REDUCTION       = 6;


    /**
     * @var string[] $values
     */
    protected static $values = [
        self::KINGDOM_DEFENCE                 => 0,
        self::KINGDOM_RESOURCE_GAIN           => 1,
        self::KINGDOM_UNIT_COST_REDUCTION     => 2,
        self::KINGDOM_BUILDING_COST_REDUCTION => 3,
        self::UNLOCKS_BUILDING                => 4,
        self::IRON_COST_REDUCTION             => 5,
        self::POPULATION_COST_REDUCTION       => 6,
    ];

    /**
     * @var string[] $namedValues
     */
    public static $namedValues = [
        0  => 'Kingdom Defence',
        1  => 'Kingdom Resource Gain',
        2  => 'Kingdom Unit Cost Reduction',
        3  => 'Kingdom Building Cost Reduction',
        4  => 'Unlocks New Building',
        5  => 'Iron Cost Reduction',
        6  => 'Population Cost Reduction',
    ];

    /**
     * NpcTypes constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Effects kingdom defence.
     *
     * @return bool
     */
    public function isDefence(): bool {
        return $this->value === self::KINGDOM_DEFENCE;
    }

    /**
     * Effects resource gain?
     *
     * @return bool
     */
    public function isResourceGain(): bool {
        return $this->value === self::KINGDOM_RESOURCE_GAIN;
    }

    /**
     * Effects unit cost reduction
     *
     * @return bool
     */
    public function isUnitCostReduction(): bool {
        return $this->value === self::KINGDOM_UNIT_COST_REDUCTION;
    }

    /**
     * Unlocks a specific building
     *
     * @return bool
     */
    public function unlocksBuilding(): bool {
        return $this->value === self::UNLOCKS_BUILDING;
    }

    /**
     * Is iron cost reduction.
     *
     * @return bool
     */
    public function isIronCostReduction(): bool {
        return $this->value === self::IRON_COST_REDUCTION;
    }

    /**
     * Is population cost reduction.
     *
     * @return bool
     */
    public function isPopulationCostReduction(): bool {
        return $this->value === self::POPULATION_COST_REDUCTION;
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
