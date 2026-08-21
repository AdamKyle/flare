<?php

namespace App\Game\Automation\BatchCrafting\Attributes;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class HandlesBatchCraftingType
{
    /**
     * @param  BatchCraftingType  $type
     */
    public function __construct(public readonly BatchCraftingType $type) {}
}
