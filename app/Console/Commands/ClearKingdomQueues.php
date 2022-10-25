<?php

namespace App\Console\Commands;

use App\Flare\Models\Kingdom;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ClearKingdomQueues extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:kingdom-queues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears kingdom queues';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        $bar = $this->output->createProgressBar(Kingdom::count());

        Kingdom::chunkById(100, function($kingdoms) use ($bar) {
            foreach ($kingdoms as $kingdom) {
                $this->deleteQueues($kingdom->unitsQueue);
                $this->deleteQueues($kingdom->buildingsQueue);

                $bar->advance();
            }
        });

        $bar->finish();
    }

    protected function deleteQueues(Collection $queues) {
        foreach ($queues as $queue) {

            if ($queue->completed_at->lessThan(now())  > 0) {
                $queue->delete();
            }
        }
    }
}
