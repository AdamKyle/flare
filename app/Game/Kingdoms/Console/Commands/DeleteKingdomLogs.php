<?php

namespace App\Game\Kingdoms\Console\Commands;

use App\Flare\Models\KingdomLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteKingdomLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:kingdomLogs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clean kingdom logs that are at least seven days old.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        KingdomLog::where('created_at', '<=', Carbon::today()->subDays(7))->chunkById(100, function ($logs) {
            foreach ($logs as $log) {
                $log->delete();
            }
        });

        $this->info('Logs cleaned');
    }
}
