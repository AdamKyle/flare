<?php

namespace App\Game\Maps\Contracts;

use App\Game\Maps\Values\Coordinates;

interface CoordinatesQuery
{
    /**
     * Return the game world's cached X/Y coordinate grid.
     */
    public function get(): Coordinates;
}
