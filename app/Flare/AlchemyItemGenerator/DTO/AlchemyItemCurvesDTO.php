<?php

namespace App\Flare\AlchemyItemGenerator\DTO;

class AlchemyItemCurvesDTO
{
    private array $craftingLevelCurve;

    private array $modifierCurve;

    private array $goldDustCostCurve;

    private array $shardsCostCurve;

    public function setCraftingLevelCurve(array $craftingLevelCurve): AlchemyItemCurvesDTO
    {
        $this->craftingLevelCurve = $craftingLevelCurve;

        return $this;
    }

    public function setModifiersCurve(array $modifierCurve): AlchemyItemCurvesDTO
    {
        $this->modifierCurve = $modifierCurve;

        return $this;
    }

    public function setGoldDustCurve(array $goldDustCostCurve): AlchemyItemCurvesDTO
    {
        $this->goldDustCostCurve = $goldDustCostCurve;

        return $this;
    }

    public function setShardsCostCurve(array $shardsCostCurve): AlchemyItemCurvesDTO
    {
        $this->shardsCostCurve = $shardsCostCurve;

        return $this;
    }

    public function getCraftingLevelCurve(): array
    {
        return $this->craftingLevelCurve;
    }

    public function getModifierCurve(): array
    {
        return $this->modifierCurve;
    }

    public function getGoldDustCostCurve(): array
    {
        return $this->goldDustCostCurve;
    }

    public function getShardsCostCurve(): array
    {
        return $this->shardsCostCurve;
    }
}
