<?php

namespace App\Flare\AlchemyItemGenerator\DTO;

class AlchemyItemCurvesDTO
{
    /**
     * @var array $craftingLevelCurve
     */
    private array $craftingLevelCurve;

    /**
     * @var array $modifierCurve
     */
    private array $modifierCurve;

    /**
     * @var array $goldDustCostCurve
     */
    private array $goldDustCostCurve;

    /**
     * @var array $shardsCostCurve
     */
    private array $shardsCostCurve;

    /**
     * Sets the crafting level curve data.
     *
     * @param array $craftingLevelCurve
     * @return AlchemyItemCurvesDTO
     */
    public function setCraftingLevelCurve(array $craftingLevelCurve): AlchemyItemCurvesDTO
    {
        $this->craftingLevelCurve = $craftingLevelCurve;
        return $this;
    }

    /**
     * Sets the modifier curve data.
     *
     * @param array $modifierCurve
     * @return AlchemyItemCurvesDTO
     */
    public function setModifiersCurve(array $modifierCurve): AlchemyItemCurvesDTO
    {
        $this->modifierCurve = $modifierCurve;
        return $this;
    }

    /**
     * Sets the gold dust cost curve data.
     *
     * @param array $goldDustCostCurve
     * @return AlchemyItemCurvesDTO
     */
    public function setGoldDustCurve(array $goldDustCostCurve): AlchemyItemCurvesDTO
    {
        $this->goldDustCostCurve = $goldDustCostCurve;
        return $this;
    }

    /**
     * Sets the shards cost curve data.
     *
     * @param array $shardsCostCurve
     * @return AlchemyItemCurvesDTO
     */
    public function setShardsCostCurve(array $shardsCostCurve): AlchemyItemCurvesDTO
    {
        $this->shardsCostCurve = $shardsCostCurve;
        return $this;
    }

    /**
     * Gets the crafting level curve data.
     *
     * @return array
     */
    public function getCraftingLevelCurve(): array
    {
        return $this->craftingLevelCurve;
    }

    /**
     * Gets the modifier curve data.
     *
     * @return array
     */
    public function getModifierCurve(): array
    {
        return $this->modifierCurve;
    }

    /**
     * Gets the gold dust cost curve data.
     *
     * @return array
     */
    public function getGoldDustCostCurve(): array
    {
        return $this->goldDustCostCurve;
    }

    /**
     * Gets the shards cost curve data.
     *
     * @return array
     */
    public function getShardsCostCurve(): array
    {
        return $this->shardsCostCurve;
    }
}
