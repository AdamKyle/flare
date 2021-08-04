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

class AssignCharactersNewStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'characters:assign-new-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns new stats to characters';

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
                $baseStatValue = resolve(BaseStatValue::class)->setRace($character->race)->setClass($character->class);
                $agi   = $baseStatValue->agi() + $character->level;
                $focus = $baseStatValue->focus() + $character->level;

                $character->update([
                    'agi'   => $agi,
                    'focus' => $focus,
                ]);

                dump($character->refresh());
            });
        });
    }
}
