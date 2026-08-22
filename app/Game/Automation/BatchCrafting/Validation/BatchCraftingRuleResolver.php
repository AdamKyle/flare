<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;

class BatchCraftingRuleResolver
{
    public function __construct(
        private readonly CraftBatchCraftingRules $craftRules,
        private readonly CraftAndEnchantBatchCraftingRules $craftAndEnchantRules,
        private readonly EnchantBatchCraftingRules $enchantRules,
        private readonly AlchemyBatchCraftingRules $alchemyRules,
        private readonly HolyOilsBatchCraftingRules $holyOilsRules,
        private readonly TrinketryBatchCraftingRules $trinketryRules,
    ) {}

    /**
     * Build the type-specific validation rules for the requested Batch Crafting type.
     *
     * @param  BatchCraftingType  $type  The requested Batch Crafting type.
     * @param  array  $requestData  The raw Batch Crafting request data.
     * @return array The type-specific validation rules.
     */
    public function rules(BatchCraftingType $type, array $requestData): array
    {
        return match ($type) {
            BatchCraftingType::CRAFT => $this->craftRules->rules($requestData),
            BatchCraftingType::CRAFT_AND_ENCHANT => $this->craftAndEnchantRules->rules($requestData),
            BatchCraftingType::ENCHANT => $this->enchantRules->rules($requestData),
            BatchCraftingType::ALCHEMY => $this->alchemyRules->rules($requestData),
            BatchCraftingType::HOLY_OILS => $this->holyOilsRules->rules($requestData),
            BatchCraftingType::TRINKETRY => $this->trinketryRules->rules($requestData),
        };
    }
}
