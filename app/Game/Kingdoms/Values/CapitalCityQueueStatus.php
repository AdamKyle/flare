<?php

namespace App\Game\Kingdoms\Values;

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

    const CANCELLED = 'cancelled';

    const CANCELLATION_REJECTED = 'cancellation_rejected';
}
