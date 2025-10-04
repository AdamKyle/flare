<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
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

        $selectedArfitfactName = $this->choice('Which item?', Item::where('type', 'artifact')->whereDoesntHave('inventorySlots')->whereDoesntHave('inventorySetSlots')->pluck('name')->toArray());

        $foundArtifact = Item::where('name', $selectedArfitfactName)->first();

        $this->giveAncientReward($character, $foundArtifact->id);
    }
}
