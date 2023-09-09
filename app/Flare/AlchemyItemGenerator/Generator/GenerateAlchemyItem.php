<?php

namespace App\Flare\AlchemyItemGenerator\Generator;

use Illuminate\Support\Str;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemCurvesDTO;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemDTO;
use App\Flare\AlchemyItemGenerator\Values\AlchemyItemType;
use App\Flare\Models\Item;

class GenerateAlchemyItem {

    const INCREASE_STATS_LENGTH = 30;
    const INCREASE_BASE_MOD_LENGTH = 15;

    public function generateAlchemyItem(AlchemyItemDTO $alchemyItemDTO, AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void {

        $alchemyItemType = new AlchemyItemType($alchemyItemDTO->getType());

        if ($alchemyItemType->increasesStats()) {
            $this->generateStatIncreasingItems($alchemyItemCurvesDTO);
        }

        if ($alchemyItemType->increasesDamage()) {
            $this->generateDamageIncreaseItems($alchemyItemCurvesDTO);
        }

        if ($alchemyItemType->increasesArmour()) {
            $this->generateArmourIncreaseItems($alchemyItemCurvesDTO);
        }

        if ($alchemyItemType->increasesHealing()) {
            $this->generateHealingIncreaseItems($alchemyItemCurvesDTO);
        }

        if ($alchemyItemType->increasesSkillType()){
            $this->generateSkillTypeIncreaseItems($alchemyItemCurvesDTO, $alchemyItemDTO->getSkillType());
        }
    }

    protected function generateStatIncreasingItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO) {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();

        $count = count($skillLevelCurve['trivial']) - 1;

        $modifierCurve     = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'            => self::INCREASE_STATS_LENGTH,
                    'increase_stat_by'     => $modifierCurve[$i],
                    'gold_dust_cost'       => $goldDustCostCurve[$i],
                    'shards_cost'          => $shardsCostCurve[$i],
                    'skill_level_required' => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'  => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    protected function generateDamageIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO) {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();

        $count = count($skillLevelCurve['trivial']) - 1;

        $modifierCurve     = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'            => self::INCREASE_BASE_MOD_LENGTH,
                    'base_damage_mod'      => $modifierCurve[$i],
                    'gold_dust_cost'       => $goldDustCostCurve[$i],
                    'shards_cost'          => $shardsCostCurve[$i],
                    'skill_level_required' => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'  => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    protected function generateArmourIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO) {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();

        $count = count($skillLevelCurve['trivial']) - 1;

        $modifierCurve     = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'            => self::INCREASE_BASE_MOD_LENGTH,
                    'base_ac_mod'          => $modifierCurve[$i],
                    'gold_dust_cost'       => $goldDustCostCurve[$i],
                    'shards_cost'          => $shardsCostCurve[$i],
                    'skill_level_required' => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'  => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    protected function generateHealingIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO) {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();

        $count = count($skillLevelCurve['trivial']) - 1;

        $modifierCurve     = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'            => self::INCREASE_BASE_MOD_LENGTH,
                    'base_healing_mod'     => $modifierCurve[$i],
                    'gold_dust_cost'       => $goldDustCostCurve[$i],
                    'shards_cost'          => $shardsCostCurve[$i],
                    'skill_level_required' => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'  => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    protected function generateSkillTypeIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO, int $skillType) {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();

        $count = count($skillLevelCurve['trivial']) - 1;

        $modifierCurve     = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'            => self::INCREASE_BASE_MOD_LENGTH,
                    'affects_skill_type'   => $skillType,
                    'skill_training_bonus' => $modifierCurve[$i],
                    'skill_bonus'          => $modifierCurve[$i],
                    'gold_dust_cost'       => $goldDustCostCurve[$i],
                    'shards_cost'          => $shardsCostCurve[$i],
                    'skill_level_required' => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'  => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    private function createBaseAttributes(): array {
        return [
            'name'                 => Str::random(8),
            'description'          => Str::random(8),
            'can_stack'            => true,
            'can_craft'            => true,
            'type'                 => 'alchemy',
            'crafting_type'        => 'alchemy',
            'usable'               => true,
        ];
    }
}
