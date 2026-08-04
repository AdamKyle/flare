<?php

namespace App\Game\Core\Chance;

class PhpRandomNumberGenerator implements RandomNumberGenerator
{
    public function numberBetween(int $minimum, int $maximum): int
    {
        return random_int($minimum, $maximum);
    }
}
