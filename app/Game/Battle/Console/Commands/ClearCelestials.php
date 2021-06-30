<?php

namespace App\Game\Battle\Console\Commands;

use App\Admin\Services\ItemAffixService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Skill;
use App\Game\Battle\Events\UpdateCelestialFight;
use App\Game\Messages\Events\GlobalMessageEvent;
use Carbon\Carbon;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Console\Command;

class ClearCelestials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:celestials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears celestials older then an hour';

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
    public function handle() {
        $count = CelestialFight::whereDate('updated_at', '<=', now()->subHour())->count();

        if ($count > 0) {
            CelestialFight::where('updated_at', '<=', now()->subHour())->chunkById(100, function ($fights) {
                foreach ($fights as $fight) {
                    CharacterInCelestialFight::where('celestial_fight_id', $fight->id)->delete();

                    event(new GlobalMessageEvent($fight->monster->name . ' has vanished from the surface plane (hourly reset).'));

                    $fight->delete();

                    event(new UpdateCelestialFight(null, true));
                }
            });
        }
    }
}
