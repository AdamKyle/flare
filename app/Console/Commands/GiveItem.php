<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class GiveItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:item {characterId} {itemId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give an item to a character';

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
        $character = Character::find($this->argument('characterId'));

        if (is_null($character)) {
            return $this->error('Character does not exist.');
        }

        $item = Item::find($this->argument('itemId'));

        if (is_null($item)) {
            return $this->error('Item does not exist.');
        }

        if (!($character->inventory->slots->count() < $character->inventory_max)) {
            return $this->error('Cannot give item to player whos inventory is maxed out.');
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
            'equipped'     => false,
            'position'     => null,
        ]);

        $this->info('Gave item to player.');
    }
}