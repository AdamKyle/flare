<?php

namespace App\Game\Raids\Values;

class RaidIdentity
{
    /**
     * @param int $id
     * @param string $raidType
     * @param string $name
     */
    public function __construct(
        public readonly int $id,
        public readonly string $raidType,
        public readonly string $name,
    ) {}
}
