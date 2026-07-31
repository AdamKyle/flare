<?php

namespace App\Flare\GemWorldGeneration\Values;

class GemWorldLocationPlacement
{
    public function __construct(
        public readonly string $type,
        public readonly int $x,
        public readonly int $y,
    ) {}
}
