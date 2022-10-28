<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use Illuminate\Console\Command;

class RemoveInvalidQuestItemsFromCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove-invalid-quest-items:from-characters';

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
        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                $this->removeItems($character);
            }
        });
    }

    protected function removeItems(Character $character): void {
        $inventory = $character->inventory->slots;

        $invalidItemNames = [];

        foreach ($inventory as $slot) {
            if ($slot->item->type === 'quest') {
                $quest = Quest::where('item_id', $slot->item_id)->orWhere('secondary_required_item', $slot->item->id)->first();

                if (!is_null($quest)) {
                    $completedQuest = $character->questsCompleted()->where('quest_id', $quest->id)->first();

                    if (!is_null($completedQuest)) {
                        $invalidItemNames[] = $slot->item->name;

                        $slot->delete();
                    }
                }
            }
        }

        $this->newLine();
        $this->line('Deleted the following items from: ' . $character->name);
        $this->newLine();

        foreach($invalidItemNames as $name) {
            $this->line($name);
        }

        $invalidItemNames;
    }
}
