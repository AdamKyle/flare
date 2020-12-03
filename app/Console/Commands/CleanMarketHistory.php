<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanMarketHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:market-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans the last 60 days of history from the market.';

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
        MarketHistory::where('created_at', '>=', Carbon::today()->subDays(60))->chunkById(100, function($histories) {
            foreach($histories as $history) {
                $history->delete();
            }
        });

        $this->info('cleaned');
    }
}
