<?php

namespace App\Console\Commands;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use File;
use Illuminate\Console\Command;

class RedistributeGold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:character-gold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes character gold to be max.';

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
           foreach ($characters as $character) {
               if ($character->gold > MaxCurrenciesValue::MAX_GOLD) {
                   $character->update([
                       'gold' => MaxCurrenciesValue::MAX_GOLD,
                   ]);

                   event(new UpdateTopBarEvent($character->refresh()));
               }
           }
        });
    }
}