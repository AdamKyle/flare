<?php

namespace App\Console\Commands;

use App\Flare\Jobs\CreateCharacterAttackData;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterAttackTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class CreateCharacterAttackDataCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:character-attack-data';

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

        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                CreateCharacterAttackData::dispatch($character->id);
            }
        });

        $this->line('Done Creating Character Attack Data Jobs...');
    }
}
