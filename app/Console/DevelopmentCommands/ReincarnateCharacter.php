<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Game\Reincarnate\Services\CharacterReincarnateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ReincarnateCharacter extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reincarnate:character {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reincarnates a character';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(CharacterReincarnateService $characterReincarnateService) {
        $name      = $this->argument('characterName');
        $character = Character::where('name', $name)->first();

        if (is_null($character)) {
            $this->error('No Character found for name: ' . $name);

            return;
        }

        $this->line('Reincarnating. Might take a long while ....');

        $character = $this->reincarnateCharacter($character, $characterReincarnateService);

        Artisan::call('level:character ' . $character->id . ' ' . 4999);

        $this->line('reincarnation is done, character max stats are now 9,999,999,999. Character also leveled back to max level.');
    }

    /**
     * Reincarnate character.
     *
     * @param Character $character
     * @param CharacterReincarnateService $characterReincarnateService
     * @return Character
     */
    protected function reincarnateCharacter(Character $character, CharacterReincarnateService $characterReincarnateService): Character {
        $result = $characterReincarnateService->doReincarnation($character);

        $character = $character->refresh();

        if ($result['status'] !== 422) {
            $this->reincarnateCharacter($character, $characterReincarnateService);
        }

        return $character;
    }
}
