<?php

namespace App\Flare\AlchemyItemGenerator\Console\Commands;

use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemCurvesDTO;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemDTO;
use App\Flare\AlchemyItemGenerator\Generator\GenerateAlchemyItem;
use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\ExponentialCurve\Curve\ExponentialLevelCurve;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Illuminate\Console\Command;

class MassGenerateAlchemyItems extends Command
{
    /**
     * @var string $signature
     */
    protected $signature = 'generate:alchemy-items {amount=25} {minLevel=1} {minCost=100}';

    /**
     * @var string $description
     */
    protected $description = 'Can generate a set of 56 enchantments based on user supplied input.';

    /**
     * Executes the console command to generate alchemical items.
     *
     * @param AlchemyItemDTO $alchemyItemDTO
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param ExponentialLevelCurve $exponentialLevelCurve
     * @param GenerateAlchemyItem $generateAlchemyItem
     * @return void
     */
    public function handle(
        AlchemyItemDTO $alchemyItemDTO,
        AlchemyItemCurvesDTO $alchemyItemCurvesDTO,
        ExponentialAttributeCurve $exponentialAttributeCurve,
        ExponentialLevelCurve $exponentialLevelCurve,
        GenerateAlchemyItem $generateAlchemyItem
    ): void {
        $this->line('Hello, today we are going to generate alchemical items based on a specific type.');

        $type = $this->alchemicalTypesChoice();

        $skillType = null;

        if ($this->increasesSkillType($type)) {
            $skillType = $this->getSkillType();
            $skillType = array_search($skillType, SkillTypeValue::getValues());
        }

        $alchemyItemDTO = $alchemyItemDTO->setType($type)->setSkillType($skillType);

        $this->line('Generating Curves for items...');

        $amount = $this->argument('amount');

        $skillLevelsRequired = $exponentialLevelCurve->generateSkillLevels(
            $this->argument('minLevel'),
            200,
            $amount
        );

        $modifierCurve = $exponentialAttributeCurve->setMin(0.15)->setMax(3.0)->setRange(1.0)
            ->setIncrease(0.05)
            ->generateValues($amount);

        $goldDustCostCurve = $exponentialAttributeCurve->setMin($this->argument('minCost'))
            ->setMax(50000000)
            ->setRange(250)
            ->setIncrease(150)
            ->generateValues($amount, true);

        $shardCostCurve = $exponentialAttributeCurve->setMin($this->argument('minCost'))
            ->setMax(50000000)
            ->setRange(250)
            ->setIncrease(150)
            ->generateValues($amount, true);

        $alchemyItemCurvesDTO = $alchemyItemCurvesDTO->setCraftingLevelCurve($skillLevelsRequired)
            ->setModifiersCurve($modifierCurve)
            ->setGoldDustCurve($goldDustCostCurve)
            ->setShardsCostCurve($shardCostCurve);

        $this->line('Building alchemy items ...');

        $generateAlchemyItem->generateAlchemyItem($alchemyItemDTO, $alchemyItemCurvesDTO);

        $this->line('All done :)');
    }

    /**
     * Presents a choice of alchemical item types to the user and returns the selected type.
     *
     * @return string
     */
    private function alchemicalTypesChoice(): string
    {
        return $this->choice('What type of alchemical items should we generate?', [
            AlchemyItemType::INCREASE_STATS->value,
            AlchemyItemType::INCREASE_DAMAGE->value,
            AlchemyItemType::INCREASE_ARMOUR->value,
            AlchemyItemType::INCREASE_HEALING->value,
            AlchemyItemType::INCREASE_SKILL_TYPE->value,
            AlchemyItemType::DAMAGES_KINGDOMS->value,
            AlchemyItemType::HOLY_OILS->value,
        ]);
    }

    /**
     * Presents a choice of skill types to the user and returns the selected skill type.
     *
     * @return string
     */
    private function getSkillType(): string
    {
        return $this->choice('Which skill type should this effect?', SkillTypeValue::getValues());
    }

    /**
     * Determines if the given alchemical item type increases a skill type.
     *
     * @param string $type
     * @return bool
     */
    private function increasesSkillType(string $type): bool
    {
        try {
            return AlchemyItemType::from($type)->increasesSkillType();
        } catch (Exception $e) {
            $this->error('Woah ERROR: ' . $e->getMessage());
            $this->exit();
        }
    }
}
