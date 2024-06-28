<?php


namespace App\Game\Kingdoms\Values;

use App\Flare\Models\KingdomBuilding;

class CapitalCityQueueStatus
{

    const TRAVELING = 'traveling';
    const PROCESSING = 'processing';
    const REQUESTING = 'requesting';
    const BUILDING = 'building';
    const REPAIRING = 'repairing';
    const RECRUITING = 'recruiting';
    const REJECTED = 'rejected';
    const FINISHED = 'finished';
}
