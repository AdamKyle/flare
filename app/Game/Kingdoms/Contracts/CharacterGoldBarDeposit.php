<?php

namespace App\Game\Kingdoms\Contracts;

interface CharacterGoldBarDeposit
{
    /**
     * Deposit up to the requested Gold Bars across the Character's Kingdoms and return the amount deposited.
     *
     * @param int $characterId
     * @param int $requestedAmount
     * @return int
     */
    public function deposit(int $characterId, int $requestedAmount): int;
}
