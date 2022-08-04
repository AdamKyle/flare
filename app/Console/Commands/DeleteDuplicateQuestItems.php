<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class DeleteDuplicateQuestItems extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete-duplicate:quest_items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Duplicate Quest items';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $questItemsIds = Item::where('type', 'quest')->pluck('id')->toArray();

        Character::chunkById(100, function($characters) use ($questItemsIds) {
            foreach ($characters as $character) {

                foreach ($questItemsIds as $questItemsId) {
                    $amount = $character->inventory->slots->where('item_id', $questItemsId)->count();

                    if ($amount > 1) {
                        $questItemName = Item::find($questItemsId)->name;

                        $newAmount = $amount - 1;

                        for ($i = 1; $i <= $newAmount; $i++) {
                            $character->inventory->slots()->where('item_id', $questItemsId)->delete();
                        }

                        $this->line('Deleted quest item: ' . $questItemName . '(ID: '.$questItemsId.') from character: ' . $character->name . ' amount deleted: ' . $newAmount);

                        $character = $character->refresh();

                        $newAmountOfQuestItems = $character->inventory->slots->where('item_id', $questItemsId)->count();

                        if ($newAmountOfQuestItems === 0) {
                            $character->inventory->slots()->create([
                                'inventory_id' => $character->inventory->id,
                                'item_id'      => $questItemsId
                            ]);

                            $this->line('Gave quest item: ' . $questItemName . '(ID: '.$questItemsId.') back to character: ' . $character->name);
                        }
                    }
                }
            }
        });
    }
}
