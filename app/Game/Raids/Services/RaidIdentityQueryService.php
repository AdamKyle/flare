<?php

namespace App\Game\Raids\Services;

use App\Flare\Models\Raid;
use App\Game\Raids\Contracts\RaidIdentityQuery;
use App\Game\Raids\Values\RaidIdentity;
use App\Game\Raids\Values\RaidType;

class RaidIdentityQueryService implements RaidIdentityQuery
{
    /**
     * Return the current identity snapshot for the Raid with the given id.
     *
     * @param int $raidId
     * @return ?RaidIdentity
     */
    public function forId(int $raidId): ?RaidIdentity
    {
        $raid = Raid::find($raidId);

        if (is_null($raid) || is_null($raid->raid_type)) {
            return null;
        }

        return new RaidIdentity(
            $raid->id,
            $raid->raid_type,
            (new RaidType($raid->raid_type))->getNameForRaid(),
        );
    }
}
