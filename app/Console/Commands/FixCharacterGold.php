<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use Illuminate\Console\Command;

class FixCharacterGold extends Command
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
    protected $description = 'Fixes Character Gold when characters are capped and some how are going over the cap.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        Character::where('gold', '>', MaxCurrenciesValue::MAX_GOLD)->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);
    }
}
