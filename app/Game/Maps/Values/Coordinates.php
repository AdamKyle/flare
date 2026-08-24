<?php

namespace App\Game\Maps\Values;

class Coordinates
{
    public function __construct(
        public readonly array $x,
        public readonly array $y,
    ) {}
}
