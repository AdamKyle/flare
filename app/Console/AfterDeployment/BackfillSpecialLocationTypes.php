<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillSpecialLocationTypes extends Command
{
    protected $signature = 'backfill:special-location-types';

    protected $description = 'Backfills SPECIAL location type for enemy-strength locations that do not have an explicit type.';

    public function handle(): void
    {
        $skipped = Location::whereNotNull('enemy_strength_type')
            ->whereNotNull('type')
            ->count();

        $updated = Location::whereNotNull('enemy_strength_type')
            ->whereNull('type')
            ->update([
                'type' => LocationType::SPECIAL->value,
            ]);

        $this->line("Updated {$updated} special location type(s).");
        $this->line("Skipped {$skipped} already typed location(s).");

        Log::info('Backfilled special location types.', [
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }
}
