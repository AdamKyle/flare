<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Notification;
use App\Flare\Models\Skill;
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

        $this->line('making sure Alchemy is locked ...');
        // Make sure all character skill's Alchemy are locked.
        Skill::where('game_skill_id', GameSkill::where('name', 'Alchemy')->first()->id)->update([
            'is_locked' => true,
        ]);
        $this->line('Updating Item prices ...');
        // Make sure all items and affixes are priced properly:
        Item::where('cost', '<=', 1000)->where('usable', false)->update(['can_drop' => true]);
        Item::where('cost', '>', 1000)->where('usable', false)->update(['can_drop' => false]);
        $this->line('Updating Item Affixes prices ...');
        ItemAffix::where('cost', '<=', 1500)->update(['can_drop' => true]);
        ItemAffix::where('cost', '>', 1500)->update(['can_drop' => false]);
    }
}
