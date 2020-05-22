<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use Illuminate\Console\Command;

class GiveCharacterGold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:gold {characterId} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give a character gold.';

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
            return $this->error('Character not found');
        }

        $character->gold = $this->argument('amount');
        $character->save();

        $character->refresh();

        $this->info('Gave: ' . $character->name . ' ' . $this->argument('amount') . ' gold.');
    }
}
