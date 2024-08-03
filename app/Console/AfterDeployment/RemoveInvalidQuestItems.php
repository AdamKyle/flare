<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class RemoveInvalidQuestItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:invalid-quest-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove and delete invalid quest items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Character::chunkById(100, function ($characters) {
            foreach ($characters as $character) {

                if (is_null($character->inventory)) {
                    continue;
                }

                $character->inventory->slots()
                    ->whereHas('item', function ($query) {
                        $query->where('type', 'quest')
                            ->where(function ($query) {
                                $query->whereNotNull('item_suffix_id')
                                    ->orWhereNotNull('item_prefix_id');
                            });
                    })
                    ->delete();

            }
        });

        Item::where('type', 'quest')
            ->where(function ($query) {
                $query->whereNotNull('item_suffix_id')
                    ->orWhereNotNull('item_prefix_id');
            })->delete();
    }
}
