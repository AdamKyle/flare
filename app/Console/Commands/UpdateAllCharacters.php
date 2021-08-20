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

class UpdateAllCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:character-1-1-0';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates characters for 1.1.0';

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
        $this->call('update-races-classes:assign-stats');
        $this->call('class:assign-to-hit-stat');
        $this->call('characters:assign-new-stats');
        $this->call('characters:assign-inventory-sets');
        $this->call('give:skills');
    }
}
