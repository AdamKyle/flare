<?php

namespace Tests\Traits;

use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;

trait CreateMonitoredSystemError
{
    public function createMonitoredSystemErrorReport(array $options = []): MonitoredSystemErrorReport
    {
        return MonitoredSystemErrorReport::factory()->create($options);
    }

    public function createMonitoredSystemErrorOccurrence(array $options = []): MonitoredSystemErrorOccurrence
    {
        return MonitoredSystemErrorOccurrence::factory()->create($options);
    }
}
