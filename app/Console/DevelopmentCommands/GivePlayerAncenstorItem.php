<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Game\Battle\Concerns\HandleGivingAncestorItem;
use Illuminate\Console\Command;

class GivePlayerAncenstorItem extends Command
{
    use HandleGivingAncestorItem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give-player:ancenstor-item {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives random ancenstor item to player';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $character = Character::where('name', $this->argument('characterName'))->first();

        if (is_null($character)) {
            return $this->error('No character found for name: '.$this->argument('characterName'));
        }

        $this->giveAncientReward($character);
    }
}
