<?php

namespace App\Console\Commands;

use App\Admin\Services\ItemAffixService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Skill;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Services\CharacterService;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GiveCharactersGold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:gold {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give all characters an amount of gold.';

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
     * @return int
     */
    public function handle()
    {
        $this->line('Giving gold to characters ....');

        Character::chunkById(200, function($characters) {
            foreach ($characters as $character) {
                $character->update(['gold' => $character->gold + $this->argument('amount')]);
            }
        });

        $this->line('Gave gold to all characters');
    }
}
