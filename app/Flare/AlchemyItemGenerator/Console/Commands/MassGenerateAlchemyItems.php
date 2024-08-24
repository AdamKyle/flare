<?php

namespace App\Flare\AlchemyItemGenerator\Console\Commands;

use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemCurvesDTO;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemDTO;
use App\Flare\AlchemyItemGenerator\Generator\GenerateAlchemyItem;
use App\Flare\AlchemyItemGenerator\Values\AlchemyItemType;
use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\ExponentialCurve\Curve\ExponentialLevelCurve;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Illuminate\Console\Command;

class MassGenerateAlchemyItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:alchemy-items {amount=25} {minLevel=1} {minCost=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Can generate a set of 56 enchantments based on user supplied input.';

    /**
     * Execute the console command.
     */
    public function handle(
        AlchemyItemDTO $alchemyItemDTO,
        AlchemyItemCurvesDTO $alchemyItemCurvesDTO,
        ExponentialAttributeCurve $exponentialAttributeCurve,
        ExponentialLevelCurve $exponentialLevelCurve,
        GenerateAlchemyItem $generateAlchemyItem
    ) {
        $this->line('Hello, today we are going to generate alchemical items based on a specific type.');

        $type = $this->alchemicalTypesChoice();

        $skillType = null;

        if ($this->increasesSkillType($type)) {
            $skillType = $this->getSkillType();

            $skillType = array_search($skillType, SkillTypeValue::$namedValues);
        }

        $alchemyItemDTO = $alchemyItemDTO->setType($type)->setSkillType($skillType);

        $this->line('Generating Curves for items...');

        $amount = $this->argument('amount');

        $skillLevelsRequired = $exponentialLevelCurve->generateSkillLevels($this->argument('minLevel'), 200, $amount);
        $modifierCurve = $exponentialAttributeCurve->setMin(0.15)->setMax(3.0)->setRange(1.0)->setIncrease(0.05)->generateValues($amount);
        $goldDustCostCurve = $exponentialAttributeCurve->setMin($this->argument('minCost'))->setMax(50000000)->setRange(250)->setIncrease(150)->generateValues($amount, true);
        $shardCostCurve = $exponentialAttributeCurve->setMin($this->argument('minCost'))->setMax(50000000)->setRange(250)->setIncrease(150)->generateValues($amount, true);

        $alchemyItemCurvesDTO = $alchemyItemCurvesDTO->setCraftingLevelCurve($skillLevelsRequired)
            ->setModifiersCurve($modifierCurve)
            ->setGoldDustCurve($goldDustCostCurve)
            ->setShardsCostCurve($shardCostCurve);

        $this->line('Building alchemy items ...');

        $generateAlchemyItem->generateAlchemyItem($alchemyItemDTO, $alchemyItemCurvesDTO);

        $this->line('All done :)');
    }

    /**
     * What type of alchemical items are we generating?
     */
    protected function alchemicalTypesChoice(): string
    {
        return $this->choice('What type of alchemical items should we generate?', [
            AlchemyItemType::INCREASE_STATS,
            AlchemyItemType::INCREASE_DAMAGE,
            AlchemyItemType::INCREASE_ARMOUR,
            AlchemyItemType::INCREASE_HEALING,
            AlchemyItemType::INCREASE_SKILL_TYPE,
            AlchemyItemType::DAMAGES_KINGDOMS,
            AlchemyItemType::HOLY_OILS,
        ]);
    }

    /**
     * What skill type should we effect?
     */
    protected function getSkillType(): string
    {
        return $this->choice('Which skill type should this effect?', SkillTypeValue::$namedValues);
    }

    /**
     * Does the type of alchemy item increase a skill type?
     */
    protected function increasesSkillType(string $type): bool
    {
        try {
            return (new AlchemyItemType($type))->increasesSkillType();
        } catch (Exception $e) {
            $this->error('Woah ERROR: '.$e->getMessage());

            $this->exit();
        }
    }
}
