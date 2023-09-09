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

        if ($alchemyItemType->increasesSkillType()) {
            $this->generateSkillTypeIncreaseItems($alchemyItemCurvesDTO, $alchemyItemDTO->getSkillType());
        }

        if ($alchemyItemType->damagesKingdoms()) {
            $this->generateDamagesKingdoms($alchemyItemCurvesDTO);
        }

        if ($alchemyItemType->isHolyOilType()) {
            $this->generateHolyOils($alchemyItemCurvesDTO);
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
                    'lasts_for'                         => self::INCREASE_BASE_MOD_LENGTH,
                    'base_ac_mod'                       => $modifierCurve[$i],
                    'gold_dust_cost'                    => $goldDustCostCurve[$i],
                    'shards_cost'                       => $shardsCostCurve[$i],
                    'increase_skill_bonus_by'           => $skillLevelCurve['required'][$i],
                    'increase_skill_training_bonus_by'  => $skillLevelCurve['trivial'][$i],
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
                    'lasts_for'                        => self::INCREASE_BASE_MOD_LENGTH,
                    'affects_skill_type'               => $skillType,
                    'increase_skill_bonus_by'          => $modifierCurve[$i],
                    'increase_skill_training_bonus_by' => $modifierCurve[$i],
                    'gold_dust_cost'                   => $goldDustCostCurve[$i],
                    'shards_cost'                      => $shardsCostCurve[$i],
                    'skill_level_required'             => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'              => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    protected function generateDamagesKingdoms(AlchemyItemCurvesDTO $alchemyItemCurvesDTO) {
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
                    'usable'                           => false,
                    'damages_kingdoms'                 => true,
                    'kingdom_damage'                   => $modifierCurve[$i],
                    'gold_dust_cost'                   => $goldDustCostCurve[$i],
                    'shards_cost'                      => $shardsCostCurve[$i],
                    'skill_level_required'             => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'              => $skillLevelCurve['trivial'][$i],
                ]
            ]);
        }
    }

    protected function generateHolyOils(AlchemyItemCurvesDTO $alchemyItemCurvesDTO) {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();

        $count = count($skillLevelCurve['trivial']) - 1;

        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'usable'                           => false,
                    'can_use_on_other_items'           => true,
                    'holy_level'                       => $i + 1,
                    'holy_stacks'                      => ($i + 1) * 2,
                    'gold_dust_cost'                   => $goldDustCostCurve[$i],
                    'shards_cost'                      => $shardsCostCurve[$i],
                    'skill_level_required'             => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'              => $skillLevelCurve['trivial'][$i],
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
