<?php

namespace App\Flare\AffixGenerator\DTO;

use Exception;

class AffixCurveDTO {

    const LEVEL_REQUIREMENTS = 'levelRequirements';
    const STAT_CURVE         = 'statCurve';
    const INTEGER_CURVE      = 'integerCurve';
    const FLOAT_CURVE        = 'floatCurve';
    const COST_CURVE         = 'costCurve';
    const INT_REQUIRED_CURVE = 'intRequiredCurve';

    /**
     * @var array $levelRequirements
     */
    private array $levelRequirements = [];

    /**
     * @var array $statCurve
     */
    private array $statCurve = [];

    /**
     * @var array $integerCurve
     */
    private array $integerCurve = [];

    /**
     * @var array $floatCurve
     */
    private array $floatCurve = [];

    /**
     * @var array $costCurve
     */
    private array $costCurve = [];

    /**
     * @var array $intRequiredCurve
     */
    private array $intRequiredCurve = [];

    /**
     * Set the level requirements
     * 
     * The array should consit of required and trivial, each as their own array of integers.
     *
     * @param array $levelRequirements
     * @return void
     */
    public function setLevelRequirements(array $levelRequirements): void {
        $this->levelRequirements = $levelRequirements;
    }

    /**
     * Set the stat curve
     *
     * @param array $statCurve
     * @return void
     */
    public function setStatCurve(array $statCurve): void {
        $this->statCurve = $statCurve;
    }

    /**
     * Set the integer curve
     *
     * @param array $integerCurve
     * @return void
     */
    public function setIntegerCurve(array $integerCurve): void {
        $this->integerCurve = $integerCurve;
    }

    /**
     * Set the floor curve
     *
     * @param array $floatCurve
     * @return void
     */
    public function setFloatCurve(array $floatCurve): void {
        $this->floatCurve = $floatCurve;
    }

    /**
     * Set the cost curve
     *
     * @param array $costCurve
     * @return void
     */
    public function setCostCurve(array $costCurve): void {
        $this->costCurve = $costCurve;
    }

    /**
     * @param array $intRequiredCurve
     * @return void
     */
    public function setIntRequiredCurve(array $intRequiredCurve): void {
        $this->intRequiredCurve = $intRequiredCurve;
    }

    /**
     * Get the value for the index based on curve name.
     * 
     * Curve names are stored as constants.
     * 
     * Can throw expcetions if we cannot find the fields we are looking for.
     *
     * @param string $curveName
     * @param integer $index
     * @throws Exception
     * @return integer|float|array
     */
    public function getValueForIndex(string $curveName, int $index): int|float|array {

        if (empty($this->{$curveName})) {
            throw new Exception($curveName . ' is empty');
        }

        if ($curveName === self::LEVEL_REQUIREMENTS) {

            if (!isset($this->levelRequirements['required'])) {
                throw new Exception($curveName . ' is missing "required" key => array');
            }

            if (!isset($this->levelRequirements['trivial'])) {
                throw new Exception($curveName . ' is missing "trivial" key => array');
            }

            if (!isset($this->levelRequirements['required'][$index])) {
                throw new Exception('Index out of bounds for ' . $curveName . ' [required]');
            }

            if (!isset($this->levelRequirements['trivial'][$index])) {
                throw new Exception('Index out of bounds for ' . $curveName . ' [trivial]');
            }

            return [
                'skill_level_required' => $this->levelRequirements['required'][$index],
                'skill_level_trivial'  => $this->levelRequirements['trivial'][$index],
            ];
        }

        if (!isset($this->{$curveName}[$index])) {
            dd($this->{$curveName}, $index);
            throw new Exception('Invalid index for array: ' . $curveName);
        }

        return $this->{$curveName}[$index];
    }
}