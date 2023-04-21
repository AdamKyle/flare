<?php

namespace App\Console\Commands;

use App\Flare\Models\MarketBoard;
use Illuminate\Console\Command;

class UnlockMarketListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unlock:market-listings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlocks market listings.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        MarketBoard::where('is_locked', true)->update(['is_locked' => false]);
    }
}
