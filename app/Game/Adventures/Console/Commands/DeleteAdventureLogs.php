<?php

namespace App\Game\Adventures\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Flare\Models\AdventureLog;

class DeleteAdventureLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:adventure-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clean adventure logs that are at least seven days old.';

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
        AdventureLog::where('created_at', '<=', Carbon::today()->subDays(7))->chunkById(100, function($logs) {
            foreach($logs as $log) {
                $log->delete();
            }
        });

        $this->info('Logs cleaned');
    }
}
