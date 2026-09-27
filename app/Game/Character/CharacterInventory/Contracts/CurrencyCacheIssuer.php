<?php

namespace App\Game\Character\CharacterInventory\Contracts;

use App\Game\Core\Currency\Values\CurrencyCacheType;

interface CurrencyCacheIssuer
{
    /**
     * Issue enough Compensation Caches to hold the requested amount and return the number created.
     *
     * @param int $characterId
     * @param CurrencyCacheType $type
     * @param int $totalAmount
     * @return int
     */
    public function issue(int $characterId, CurrencyCacheType $type, int $totalAmount): int;
}
