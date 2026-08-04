<?php

namespace App\Game\Core\Chance;

interface RandomNumberGenerator
{
    public function numberBetween(int $minimum, int $maximum): int;
}
