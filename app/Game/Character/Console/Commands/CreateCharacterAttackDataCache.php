<?php

namespace App\Game\Character\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Jobs\CreateCharacterAttackData;
use Illuminate\Console\Command;

class CreateCharacterAttackDataCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:character-attack-data {characterId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates Character Attack Data';

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

        $this->line('Creating Character Attack Data Jobs...');

        $character = Character::find($this->argument('characterId'));

        if (!is_null($character)) {
            $this->line('Creating attack data for character: ' . $character->name);

            CreateCharacterAttackData::dispatch($character->id)->onConnection('long_running');

            $this->line('Created attack data for character: ' . $character->name);

            return;
        }

        Character::chunkById(100, function ($characters) {
            foreach ($characters as $character) {
                CreateCharacterAttackData::dispatch($character->id)->onConnection('long_running');
            }
        });

        $this->line('Done Creating Character Attack Data Jobs...');
    }
}
