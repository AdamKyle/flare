<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Item;
use App\Flare\Models\MarketHistory;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SeedMarketHistoryForItemType extends Command
{
    protected $signature = 'market-history:seed-for-type
        {type : Item type to seed market history for}
        {records=150 : Number of market history records to create}';

    protected $description = 'Seed market_history with randomized prices for items of a given type.';

    private const int DAYS_BACK = 90;

    private const int INSERT_CHUNK_SIZE = 100;

    private const int MIN_PRICE = 1_000_000_000;

    private const int MAX_PRICE = 2_000_000_000;

    public function handle(): void
    {
        $type = $this->argument('type');
        $recordCount = max(1, (int) $this->argument('records'));

        $itemIds = Item::query()
            ->where('type', $type)
            ->pluck('id')
            ->all();

        if (count($itemIds) === 0) {
            $this->error('No items found for type: '.$type);

            return;
        }

        $now = CarbonImmutable::now();
        $start = $now->subDays(self::DAYS_BACK)->startOfDay();
        $secondsRange = max(1, $now->timestamp - $start->timestamp);

        $progressBar = $this->output->createProgressBar($recordCount);
        $progressBar->start();

        $records = Collection::range(1, $recordCount)
            ->map(function () use ($itemIds, $start, $secondsRange, $progressBar) {
                $randomTimestamp = $start->timestamp + random_int(0, $secondsRange);
                $createdAt = CarbonImmutable::createFromTimestamp($randomTimestamp, config('app.timezone'));
                $createdAtString = $createdAt->toDateTimeString();

                $progressBar->advance();

                return [
                    'item_id' => $itemIds[array_rand($itemIds)],
                    'sold_for' => random_int(self::MIN_PRICE, self::MAX_PRICE),
                    'created_at' => $createdAtString,
                    'updated_at' => $createdAtString,
                ];
            })
            ->values()
            ->all();

        $progressBar->finish();
        $this->newLine();

        Collection::make($records)
            ->chunk(self::INSERT_CHUNK_SIZE)
            ->each(function (Collection $chunk) {
                MarketHistory::query()->insert($chunk->values()->all());
            });

        $this->info('Inserted '.count($records).' market_history records for type: '.$type);
    }
}
