<?php

namespace App\Flare\AffixGenerator\Builders;

use Illuminate\Support\Str;
use App\Flare\AffixGenerator\DTO\AffixCurveDTO;
use App\Flare\AffixGenerator\DTO\AffixGeneratorDTO;
use Exception;
use App\Flare\Models\ItemAffix;

class AffixBuilder {

    /**
     * @var array $castArray
     */
    private array $castArray;

    public function __construct() {
        $model = new ItemAffix();

        $this->castArray = $model->getCasts();
    }

    /**
     * Generate the affix data.
     *
     * @return array
     */
    public function generateAffix(AffixGeneratorDTO $affixGeneratorDTO, AffixCurveDTO $affixCurveDTO, int $index): array {
        $affix = [];


        $affixSkillName = $affixGeneratorDTO->getSkillName();

        if (!is_null($affixSkillName)) {
            $affix['skill_name'] = $affixSkillName;
            $affix['skill_bonus'] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::FLOAT_CURVE, $index);
            $affix['skill_training_bonus'] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::FLOAT_CURVE, $index);
        }

        $affix['cost']         = $affixCurveDTO->getValueForIndex(AffixCurveDTO::COST_CURVE, $index);
        $affix['int_required'] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::INT_REQUIRED_CURVE, $index);
        $affix['type']         = $affixGeneratorDTO->getPrefixOrSuffix();

        foreach ($affixGeneratorDTO->getAttributes() as $attribute) {
            $castType = $this->getCastType($attribute);

            if (is_null($castType)) {
                throw new Exception('No cast type found for: ' . $attribute);
            }

            if ($this->isStatBased($attribute)) {
                $affix[$attribute] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::STAT_CURVE, $index);
            }

            if ($attribute === 'damage') {
                $affix[$attribute] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::INTEGER_CURVE, $index);

                if ($affixGeneratorDTO->getIsDamageIrresistible()) {
                    $affix['irresistible_damage'] = true;
                }

                if ($affixGeneratorDTO->getDoesDamageStatck()) {
                    $affix['damage_can_stack'] = true;
                }
            }

            if ($this->getCastType($attribute) === 'integer') {
                $affix[$attribute] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::INTEGER_CURVE, $index);
            }

            if ($this->getCastType($attribute) === 'float' && !$this->isStatBased($attribute)) {
                $affix[$attribute] = $affixCurveDTO->getValueForIndex(AffixCurveDTO::FLOAT_CURVE, $index);
            }
        }

        $skillLevelRequirements = $affixCurveDTO->getValueForIndex(AffixCurveDTO::LEVEL_REQUIREMENTS, $index);

        if ($skillLevelRequirements['skill_level_required'] < 10) {
            $affix['can_drop'] = true;
        }


        // Randomly generate name and description.
        $affix['name']        = Str::random(10);
        $affix['description'] = Str::random(10);
        $affix['affix_type']  = $affixGeneratorDTO->getType();

        return array_merge($affix, $skillLevelRequirements);
    }

    /**
     * Is the attribute stat based?
     *
     * @param string $attribute
     * @return boolean
     */
    protected function isStatBased(string $attribute): bool {
        $statModifiers = ['str_mod', 'dex_mod', 'agi_mod', 'int_mod', 'chr_mod', 'focus_mod', 'dur_mod', 'base_damage_mod', 'base_ac_mod', 'base_healing_mod'];

        return in_array($attribute, $statModifiers);
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
}
