<?php

namespace App\Console\Commands;

use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ChangeAffixes extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Affixes';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        // Change Damage

        $progressBar = new ProgressBar(new ConsoleOutput(), ItemAffix::where('damage', '>', 0)->count());

        ItemAffix::where('damage', '>', 0)->chunkById(100, function($itemAffixes) use($progressBar) {
            foreach ($itemAffixes as $itemAffix) {
                $itemAffix->update([
                    'damage' => $itemAffix->damage * 10,
                ]);

                $progressBar->advance();
            }
        });

        $progressBar->finish();
    }
}
