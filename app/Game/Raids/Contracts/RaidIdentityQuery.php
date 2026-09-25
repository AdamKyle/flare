<?php

namespace App\Game\Raids\Contracts;

use App\Game\Raids\Values\RaidIdentity;

interface RaidIdentityQuery
{
    /**
     * Return the current identity snapshot for the Raid with the given id.
     *
     * @param int $raidId
     * @return ?RaidIdentity
     */
    public function forId(int $raidId): ?RaidIdentity;
}
