<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use Illuminate\Console\Command;

class RemoveCandlesForXPBonus extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:candles-for-xp-bonus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'removes candles only if a specific quest is complete.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        $quest = Quest::where('name', 'Corruption in Alchemy')->first();

        $bar = $this->output->createProgressBar(Character::count());

        $bar->start();

        Character::chunkById(100, function($characters) use($quest, $bar) {
           foreach ($characters as $character) {
               $completedQuest = $character->questsCompleted()->where('quest_id', $quest->id)->first();

               if (!is_null($completedQuest)) {
                   $character->inventory->slots()->whereIn('item_id', [$quest->item_id, $quest->secondary_required_item])->delete();
               }

               $bar->advance();
           }
        });

        $bar->finish();
    }
}
