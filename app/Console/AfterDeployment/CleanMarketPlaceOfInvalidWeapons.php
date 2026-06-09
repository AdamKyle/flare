<?php

namespace App\Console\AfterDeployment;


use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Values\MaxCurrenciesValue;
use Illuminate\Console\Command;

class CleanMarketPlaceOfInvalidWeapons extends Command
{

    const INVALID_TYPE = 'weapon';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:market-weapons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans the market place of invalid weapons';

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
     */
    public function handle(): void
    {
        $this->cleanMarketListings();
        $this->cleanMarketHistory();
    }

    private function cleanMarketListings(): void {
        $marketEntries = MarketBoard::whereHas('item', function ($query) {
            $query->where('type', self::INVALID_TYPE);
        })->get();

        if ($marketEntries->isEmpty()) {
            return;
        }

        foreach ($marketEntries as $marketEntry) {
            $character = $marketEntry->character;

            $newGold = $character->gold + $marketEntry->listed_price;

            if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
                $newGold = MaxCurrenciesValue::MAX_GOLD;
            }

            $character->update([
                'gold' => $newGold,
            ]);

            $marketEntry->delete();
        }
    }

    private function cleanMarketHistory(): void {
        $marketHistory = MarketHistory::whereHas('item', function ($query) {
            $query->where('type', self::INVALID_TYPE);
        })->get();

        if ($marketHistory->isEmpty()) {
            return;
        }

        foreach ($marketHistory as $marketHistoryItem) {
            $marketHistoryItem->delete();
        }
    }
}
