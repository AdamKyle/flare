<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use InvalidArgumentException;

class BatchCraftingStatusSectionResolver
{
    /**
     * @param array<int, BatchCraftingStatusSection> $sections The registered status sections.
     */
    public function __construct(private readonly array $sections) {}

    /**
     * Resolve the registered status section for the given Batch Crafting type and mode.
     *
     * @param BatchCraftingType $type The batch's Batch Crafting type.
     * @param string $mode The batch's persisted mode value.
     * @return BatchCraftingStatusSection The resolved status section.
     */
    public function resolve(BatchCraftingType $type, string $mode): BatchCraftingStatusSection
    {
        foreach ($this->sections as $section) {
            if ($section->supports($type, $mode)) {
                return $section;
            }
        }

        throw new InvalidArgumentException('No BatchCraftingStatusSection is registered for ['.$type->value.':'.$mode.'].');
    }
}
