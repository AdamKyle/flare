<?php

namespace App\Flare\AffixGenerator\Generator;

use Exception;
use App\Flare\AffixGenerator\Curve\ExponentialAttributeCurve;
use App\Flare\AffixGenerator\Curve\ExponentialLevelCurve;
use App\Flare\AffixGenerator\DTO\AffixGeneratorDTO;
use App\Flare\Models\ItemAffix;

class GenerateAffixes {

    /**
     * @var array $castArray
     */
    private array $castArray;

    /**
     * @var array $generatedStats
     */
    private array $generatedStats;

    /**
     * @var ExponentialAttributeCurve $exponentialAttributeCurve
     */
    private ExponentialAttributeCurve $exponentialAttributeCurve;

    /**
     * @var ExponentialLevelCurve $exponentialLevelCurve
     */
    private ExponentialLevelCurve $exponentialLevelCurve;

    /**
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param ExponentialLevelCurve $exponentialLevelCurve
     */
    public function __construct(ExponentialAttributeCurve $exponentialAttributeCurve, ExponentialLevelCurve $exponentialLevelCurve) {
        
        $model = new ItemAffix();

        $this->castArray = $model->getCasts();

        $this->exponentialAttributeCurve = $exponentialAttributeCurve;
        $this->exponentialLevelCurve     = $exponentialLevelCurve;
    }

    /**
     * Generate the affixes
     *
     * @param AffixGeneratorDTO $affixGeneratorDTO
     * @return void
     */
    public function generate(AffixGeneratorDTO $affixGeneratorDTO) {

        $this->generateCurves();

        $attributes = $affixGeneratorDTO->getAttributes();

        $attribute[] = 'cost';
        $attribute[] = 'int_required';
        
        foreach ($attributes as $attribute) {
            $castType = $this->getCastType($attribute);

            if (is_null($castType)) {
                throw new Exception('No cast type found for: ' . $attribute);
            }
        }
    }

    /**
     * Get the cast type of the attribute
     * 
     * - Can be null if the attribute doesn't have a cast type.
     *
     * @param string $attributeName
     * @return string|null
     */
    protected function getCastType(string $attributeName): ?string {

        if (isset($this->castArray[$attributeName])) {
            return $this->castArray[$attributeName];
        }

        return null;
    }

    /**
     * Generate the curves needed to build the affixes.
     *
     * @return void
     */
    protected function generateCurves(): void {

        $levels = $this->exponentialLevelCurve->generateSkillLevels(1, 401);

        $size = count($levels['required']);

        $this->generatedStats['levels'] = $levels;

        $this->generateCurveForStats($size);
        $this->generateCurveForFloat($size);
        $this->generateCurveForInteger($size);
        $this->generateCurveForCost($size);
        $this->generateCurveForIntelligenceRewuired($size);
    }

    /**
     * Generate the stats curve.
     *
     * @return void
     */
    protected function generateCurveForStats(int $size): void {

        $curve = $this->exponentialAttributeCurve->setMin(0.01)
                                                 ->setMax(2.0)
                                                 ->setIncrease(0.08)
                                                 ->setRange(1.2);

        $this->generatedStats['stats'] = $curve->generateValues($size);
    }

    /**
     * Generate the curve for floats
     *
     * @return void
     */
    protected function generateCurveForFloat(int $size): void {
        $curve = $this->exponentialAttributeCurve->setMin(0.01)
                                                 ->setMax(1.0)
                                                 ->setIncrease(0.002)
                                                 ->setRange(0.20);

        $this->generatedStats['float'] = $curve->generateValues($size);
    }

    /**
     * Generate the curve for integers
     *
     * @return void
     */
    protected function generateCurveForInteger(int $size): void {
        $curve = $this->exponentialAttributeCurve->setMin(1000)
                                                 ->setMax(2000000000)
                                                 ->setIncrease(100000)
                                                 ->setRange(500);

        $this->generatedStats['integer'] = $curve->generateValues($size, true);
    }

    /**
     * Generate the curve for cost
     *
     * @return void
     */
    protected function generateCurveForCost(int $size): void {
        $curve = $this->exponentialAttributeCurve->setMin(1000)
                                                 ->setMax(40000000)
                                                 ->setIncrease(100000)
                                                 ->setRange(500);

        $this->generatedStats['cost'] = $curve->generateValues($size, true);
    }


    /**
     * Generate the curve for intelligence required.
     *
     * @return void
     */
    protected function generateCurveForIntelligenceRewuired(int $size): void {
        $curve = $this->exponentialAttributeCurve->setMin(10)
                                                 ->setMax(1000000)
                                                 ->setIncrease(1000)
                                                 ->setRange(56, true);

        $this->generatedStats['int_required'] = $curve->generateValues($size, true);
    }
}