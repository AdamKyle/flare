<?php

namespace App\Game\Automation\BatchCrafting\Attributes;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use Attribute;
use BackedEnum;

#[Attribute(Attribute::TARGET_CLASS)]
class HandlesBatchCraftingMode
{
    /**
     * @param  BatchCraftingType  $type
     * @param  BackedEnum  $mode
     */
    public function __construct(
        public readonly BatchCraftingType $type,
        public readonly BackedEnum $mode,
    ) {}
}
