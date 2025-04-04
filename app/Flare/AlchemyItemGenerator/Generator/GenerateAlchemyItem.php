<?php

namespace App\Flare\AlchemyItemGenerator\Generator;

use Illuminate\Support\Str;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemCurvesDTO;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemDTO;
use App\Flare\Models\Item;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;


class GenerateAlchemyItem
{
    /**
     * Default length for stats increase in minutes.
     */
    const INCREASE_STATS_LENGTH = 30;

    /**
     * Default length for base mod increase in minutes.
     */
    const INCREASE_BASE_MOD_LENGTH = 15;

    /**
     * Generates an Alchemy Item.
     *
     * @param AlchemyItemDTO $alchemyItemDTO
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    public function generateAlchemyItem(AlchemyItemDTO $alchemyItemDTO, AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $alchemyItemType = AlchemyItemType::from($alchemyItemDTO->getType());

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

    /**
     * Generates a stat increasing item.
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    private function generateStatIncreasingItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $modifierCurve  = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'           => self::INCREASE_STATS_LENGTH,
                    'increase_stat_by'    => $modifierCurve[$i],
                    'gold_dust_cost'      => $goldDustCostCurve[$i],
                    'shards_cost'         => $shardsCostCurve[$i],
                    'skill_level_required'=> $skillLevelCurve['required'][$i],
                    'skill_level_trivial' => $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Generates a damage increasing item.
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    private function generateDamageIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $modifierCurve  = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'           => self::INCREASE_BASE_MOD_LENGTH,
                    'base_damage_mod'     => $modifierCurve[$i],
                    'gold_dust_cost'      => $goldDustCostCurve[$i],
                    'shards_cost'         => $shardsCostCurve[$i],
                    'skill_level_required'=> $skillLevelCurve['required'][$i],
                    'skill_level_trivial' => $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Generates an armour class increasing item.
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    private function generateArmourIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $modifierCurve  = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'                       => self::INCREASE_BASE_MOD_LENGTH,
                    'base_ac_mod'                     => $modifierCurve[$i],
                    'gold_dust_cost'                  => $goldDustCostCurve[$i],
                    'shards_cost'                     => $shardsCostCurve[$i],
                    'increase_skill_bonus_by'         => $skillLevelCurve['required'][$i],
                    'increase_skill_training_bonus_by'=> $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Generates a healing increase item.
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    private function generateHealingIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $modifierCurve  = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'           => self::INCREASE_BASE_MOD_LENGTH,
                    'base_healing_mod'    => $modifierCurve[$i],
                    'gold_dust_cost'      => $goldDustCostCurve[$i],
                    'shards_cost'         => $shardsCostCurve[$i],
                    'skill_level_required'=> $skillLevelCurve['required'][$i],
                    'skill_level_trivial' => $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Generates a skill type increasing item.
     *
     * - Increases skill bonus and training bonus
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @param int $skillType
     * @return void
     */
    private function generateSkillTypeIncreaseItems(AlchemyItemCurvesDTO $alchemyItemCurvesDTO, int $skillType): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $modifierCurve  = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'lasts_for'                       => self::INCREASE_BASE_MOD_LENGTH,
                    'affects_skill_type'              => $skillType,
                    'increase_skill_bonus_by'         => $modifierCurve[$i],
                    'increase_skill_training_bonus_by'=> $modifierCurve[$i],
                    'gold_dust_cost'                  => $goldDustCostCurve[$i],
                    'shards_cost'                     => $shardsCostCurve[$i],
                    'skill_level_required'            => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'             => $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Generates a damages kingdom item.
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    private function generateDamagesKingdoms(AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $modifierCurve  = $alchemyItemCurvesDTO->getModifierCurve();
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'usable'              => false,
                    'damages_kingdoms'    => true,
                    'kingdom_damage'      => $modifierCurve[$i],
                    'gold_dust_cost'      => $goldDustCostCurve[$i],
                    'shards_cost'         => $shardsCostCurve[$i],
                    'skill_level_required'=> $skillLevelCurve['required'][$i],
                    'skill_level_trivial' => $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Generates an item that applies holy stacks to an item.
     *
     * @param AlchemyItemCurvesDTO $alchemyItemCurvesDTO
     * @return void
     */
    private function generateHolyOils(AlchemyItemCurvesDTO $alchemyItemCurvesDTO): void
    {
        $skillLevelCurve = $alchemyItemCurvesDTO->getCraftingLevelCurve();
        $count          = count($skillLevelCurve['trivial']) - 1;
        $goldDustCostCurve = $alchemyItemCurvesDTO->getGoldDustCostCurve();
        $shardsCostCurve   = $alchemyItemCurvesDTO->getShardsCostCurve();

        for ($i = 0; $i <= $count; $i++) {
            $baseAttributes = $this->createBaseAttributes();

            Item::create([
                ...$baseAttributes,
                ...[
                    'usable'                  => false,
                    'can_use_on_other_items'  => true,
                    'holy_level'              => $i + 1,
                    'holy_stacks'             => ($i + 1) * 2,
                    'gold_dust_cost'          => $goldDustCostCurve[$i],
                    'shards_cost'             => $shardsCostCurve[$i],
                    'skill_level_required'    => $skillLevelCurve['required'][$i],
                    'skill_level_trivial'     => $skillLevelCurve['trivial'][$i],
                ],
            ]);
        }
    }

    /**
     * Creates base attributes for the item.
     *
     * - Names and descriptions are randomly generated. The user would have to update the Excel file.
     *
     * @return array
     */
    private function createBaseAttributes(): array
    {
        return [
            'name'           => Str::random(8),
            'description'    => Str::random(8),
            'can_stack'      => true,
            'can_craft'      => true,
            'type'           => 'alchemy',
            'crafting_type'  => 'alchemy',
            'usable'         => true,
        ];
    }
}
