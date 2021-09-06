<?php

namespace App\Console\Commands;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Jobs\DailyGoldDustJob;
use App\Flare\Jobs\UpdateKingdomJob;
use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use Cache;
use Mail;
use Illuminate\Console\Command;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Kingdoms\Mail\KingdomsUpdated;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Facades\App\Flare\Values\UserOnlineValue;

class DailyRandomGoldDust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:gold-dust';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives random amount of gold dust to all characters per day, with chance to win lottery.';

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
     */
    public function handle() {
        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                DailyGoldDustJob::dispatch($character);
            }
        });
    }
}
