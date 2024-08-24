<?php

namespace App\Game\Kingdoms\Values;

class BuildingExpansionTypes
{
    const RESOURCE_EXPANSION = 0;

    public function isResourceExpansion(int $type): bool
    {
        return $type === self::RESOURCE_EXPANSION;
    }
}
