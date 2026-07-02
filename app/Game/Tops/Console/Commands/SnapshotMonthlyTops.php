<?php

namespace App\Game\Tops\Console\Commands;

use App\Game\Tops\Services\TopsMonthlySnapshotService;
use App\Game\Tops\Services\TopsPeriodService;
use Illuminate\Console\Command;

class SnapshotMonthlyTops extends Command
{
    protected $signature = 'game:tops:snapshot-monthly {--period-start=} {--period-end=}';

    protected $description = 'Snapshot Tops leaderboards for a completed monthly period.';

    public function __construct(
        private readonly TopsPeriodService $topsPeriodService,
        private readonly TopsMonthlySnapshotService $topsMonthlySnapshotService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        [$periodStart, $periodEnd] = $this->topsPeriodService->previousCompletedMonth(
            $this->option('period-start'),
            $this->option('period-end')
        );

        $count = $this->topsMonthlySnapshotService->snapshot($periodStart, $periodEnd);

        $this->info('Created or updated '.$count.' Tops monthly snapshot rows.');

        return self::SUCCESS;
    }
}
