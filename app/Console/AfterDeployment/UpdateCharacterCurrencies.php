<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use Illuminate\Console\Command;

class UpdateCharacterCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:character-currencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update character currencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Character::where('gold_dust', '>=', MaxCurrenciesValue::MAX_GOLD_DUST)->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        Character::where('shards', '>=', MaxCurrenciesValue::MAX_SHARDS)->update([
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);
    }
}
