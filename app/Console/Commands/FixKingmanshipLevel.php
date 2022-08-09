<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;

class FixKingmanshipLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:kingmanship-skill-max-level';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        $gameSkill = GameSkill::where('name', 'Kingmanship')->first();

        if (is_null($gameSkill)) {
            $this->error('No skill exists called Kingmanship ...');

            return;
        }

        $progressBar = new ProgressBar(new ConsoleOutput(), DB::table('characters')->count());

        Character::chunkById(100, function($characters) use ($gameSkill, $progressBar) {
            foreach ($characters as $character) {
                $skill = $character->skills()->where('game_skill_id', $gameSkill->id)->first();

                if ($skill->level > $gameSkill->max_level) {
                    $skill->update([
                        'level' => $gameSkill->max_level
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
    }
}
