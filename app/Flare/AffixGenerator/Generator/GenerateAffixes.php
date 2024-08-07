<?php

namespace App\Flare\AffixGenerator\Generator;

use App\Flare\AffixGenerator\Builders\AffixBuilder;
use App\Flare\AffixGenerator\DTO\AffixCurveDTO;
use App\Flare\AffixGenerator\DTO\AffixGeneratorDTO;
use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\ExponentialCurve\Curve\ExponentialLevelCurve;
use App\Flare\Models\ItemAffix;

class GenerateAffixes
{
    private int $amountOfAffixedToCreate = 0;

    private ExponentialAttributeCurve $exponentialAttributeCurve;

    private ExponentialLevelCurve $exponentialLevelCurve;

    private AffixCurveDTO $affixCurveDTO;

    private AffixBuilder $affixBuilder;

    public function __construct(ExponentialAttributeCurve $exponentialAttributeCurve,
        ExponentialLevelCurve $exponentialLevelCurve,
        AffixCurveDTO $affixCurveDTO,
        AffixBuilder $affixBuilder,
    ) {
        $this->exponentialAttributeCurve = $exponentialAttributeCurve;
        $this->exponentialLevelCurve = $exponentialLevelCurve;
        $this->affixCurveDTO = $affixCurveDTO;
        $this->affixBuilder = $affixBuilder;
    }

    /**
     * Generate the affixes
     *
     * @return void
     */
    public function generate(AffixGeneratorDTO $affixGeneratorDTO, int $sizeLimit)
    {

        $this->generateCurves($sizeLimit);

        $affixes = [];

        for ($i = 0; $i < $this->amountOfAffixedToCreate; $i++) {
            $affixes[] = $this->affixBuilder->generateAffix($affixGeneratorDTO, $this->affixCurveDTO, $i);
        }

        foreach ($affixes as $affix) {
            ItemAffix::create($affix);
        }
    }

    /**
     * Generate the curves needed to build the affixes.
     */
    protected function generateCurves(int $sizeLimit): void
    {

        $levels = $this->exponentialLevelCurve->generateSkillLevels(1, 401, $sizeLimit);

        $this->affixCurveDTO->setLevelRequirements($levels);

        $size = count($levels['required']);

        $this->amountOfAffixedToCreate = $size;

        $this->generateCurveForStats($size);
        $this->generateCurveForFloat($size);
        $this->generateCurveForInteger($size);
        $this->generateCurveForCost($size);
        $this->generateCurveForIntelligenceRewuired($size);
    }

    /**
     * Generate the stats curve.
     */
    protected function generateCurveForStats(int $size): void
    {

        $curve = $this->exponentialAttributeCurve->setMin(0.01)
            ->setMax(2.0)
            ->setIncrease(0.08)
            ->setRange(1.2);

        $this->affixCurveDTO->setStatCurve($curve->generateValues($size));
    }

    /**
     * Generate the curve for floats
     */
    protected function generateCurveForFloat(int $size): void
    {
        $curve = $this->exponentialAttributeCurve->setMin(0.01)
            ->setMax(1.0)
            ->setIncrease(0.002)
            ->setRange(0.20);

        $this->affixCurveDTO->setFloatCurve($curve->generateValues($size));
    }

    /**
     * Generate the curve for integers
     */
    protected function generateCurveForInteger(int $size): void
    {
        $curve = $this->exponentialAttributeCurve->setMin(50)
            ->setMax(2000000000)
            ->setIncrease(100000)
            ->setRange(500);

        $this->affixCurveDTO->setIntegerCurve($curve->generateValues($size, true));
    }

    /**
     * Generate the curve for cost
     */
    protected function generateCurveForCost(int $size): void
    {
        $curve = $this->exponentialAttributeCurve->setMin(1000)
            ->setMax(40000000000)
            ->setIncrease(100000)
            ->setRange(500);

        $this->affixCurveDTO->setCostCurve($curve->generateValues($size, true));
    }

    /**
     * Generate the curve for intelligence required.
     */
    protected function generateCurveForIntelligenceRewuired(int $size): void
    {
        $curve = $this->exponentialAttributeCurve->setMin(10)
            ->setMax(1000000)
            ->setIncrease(1000)
            ->setRange(56, true);

        $this->affixCurveDTO->setIntRequiredCurve($curve->generateValues($size, true));
    }
}
