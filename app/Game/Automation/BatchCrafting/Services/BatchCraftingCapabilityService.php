<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Services\Capabilities\AlchemyBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftAndEnchantBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\Capabilities\EnchantBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\Capabilities\HolyOilsBatchCraftingCapabilityService;
use App\Game\Automation\BatchCrafting\Services\Capabilities\TrinketryBatchCraftingCapabilityService;

class BatchCraftingCapabilityService
{
    public function __construct(
        private readonly CraftBatchCraftingCapabilityService $craftBatchCraftingCapabilityService,
        private readonly CraftAndEnchantBatchCraftingCapabilityService $craftAndEnchantBatchCraftingCapabilityService,
        private readonly EnchantBatchCraftingCapabilityService $enchantBatchCraftingCapabilityService,
        private readonly AlchemyBatchCraftingCapabilityService $alchemyBatchCraftingCapabilityService,
        private readonly HolyOilsBatchCraftingCapabilityService $holyOilsBatchCraftingCapabilityService,
        private readonly TrinketryBatchCraftingCapabilityService $trinketryBatchCraftingCapabilityService,
    ) {}

    /**
     * Build the complete Batch Crafting capability facts for the character, across every batch type.
     *
     * @param Character $character The character requesting capability facts.
     * @return array The aggregated capability facts payload.
     */
    public function build(Character $character): array
    {
        return [
            ...$this->craftBatchCraftingCapabilityService->build($character),
            ...$this->craftAndEnchantBatchCraftingCapabilityService->build($character),
            ...$this->enchantBatchCraftingCapabilityService->build($character),
            ...$this->alchemyBatchCraftingCapabilityService->build($character),
            ...$this->holyOilsBatchCraftingCapabilityService->build($character),
            ...$this->trinketryBatchCraftingCapabilityService->build($character),
        ];
    }
}
