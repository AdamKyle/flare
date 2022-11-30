<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Game\ClassRanks\Values\ClassRankValue;
use Illuminate\Console\Command;

class UpdateCharactersForClassRanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:class-ranks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns Class Ranks to Characters';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $gameClasses = GameClass::all();

        $bar = $this->output->createProgressBar(Character::count());
        $bar->start();

        Character::chunkById(100, function($characters) use($gameClasses, $bar) {
            foreach ($characters as $character) {
                foreach ($gameClasses as $gameClass) {
                    $hasGameClass = !is_null($character->classRanks()->where('game_class_id', $gameClass->id)->first());

                    if ($hasGameClass) {
                        continue;
                    }

                    $character->classRanks()->create([
                        'character_id'   => $character->id,
                        'game_class_id'  => $gameClass->id,
                        'current_xp'     => 0,
                        'required_xp'    => ClassRankValue::XP_PER_LEVEL,
                        'level'          => 0,
                    ]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }
}
