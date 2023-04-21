<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use Illuminate\Console\Command;

class AssignGemBagsToCharacters extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:gem_bags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns gem bags to characters';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        Character::chunkById(150, function($characters) {
            foreach ($characters as $character) {
                if (is_null($character->gemBag)) {
                    $character->gemBag()->create([
                        'character_id' => $character->id,
                    ]);
                }
            }
        });
    }
}
