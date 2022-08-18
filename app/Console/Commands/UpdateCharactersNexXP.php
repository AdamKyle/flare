<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class UpdateCharactersNexXP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:xp-next';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update character XP Next';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $progressBar = new ProgressBar(new ConsoleOutput(), DB::table('characters')->count());

        Character::chunkById(100, function ($characters) use($progressBar) {
            foreach ($characters as $character) {
                $character->update([
                    'xp_next' => $this->getNewXPNext($character),
                ]);

                $progressBar->advance();
            }
        });

        $progressBar->finish();
    }

    protected function getNewXPNext(Character $character): int {
        if ($character->level > 1000) {
            return (($character->level - 1000) * 10) + 100;
        }

        return 100;
    }
}
