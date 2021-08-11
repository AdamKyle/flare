<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Notification;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\CharacterClassValue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AssignInventorySetsToCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'characters:assign-inventory-sets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns inventory sets to characters that don\'t have inventory sets.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Character::chunkById(100, function($characters) {
            $characters->each(function($character) {
                if ($character->inventorySets->isEmpty()) {
                    for ($i = 1; $i <= 10; $i++) {
                        $character->inventorySets()->create([
                            'character_id'    => $character->id,
                            'can_be_equipped' => true,
                        ]);
                    }
                }
            });
        });
    }
}
