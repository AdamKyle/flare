<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Models\Character;
use Illuminate\Console\Command;

class GivePlayerMythicItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:player-mythic-item {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give a player a mythic item.';

    /**
     * Execute the console command.
     */
    public function handle(BuildMythicItem $buildMythicItem)
    {
        $character = Character::where('name', $this->argument('characterName'))->first();

        if (is_null($character)) {
            return $this->error('Character not found');
        }

        $mythic = $buildMythicItem->fetchMythicItem($character);

        $inventory = $character->inventory;

        $character->inventory->slots()->create([
            'inventory_id' => $inventory->id,
            'item_id' => $mythic->id,
        ]);

        $this->line('Gave player: '.$this->argument('characterName').' Item: '.$mythic->affix_name);
    }
}
