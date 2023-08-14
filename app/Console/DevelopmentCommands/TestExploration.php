<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\User;
use Illuminate\Support\Str;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\GameRace;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use Illuminate\Console\Command;
use App\Flare\Values\MapNameValue;
use Illuminate\Support\Facades\Hash;
use App\Flare\Builders\CharacterBuilder;
use Illuminate\Database\Eloquent\Collection;
use App\Game\Exploration\Services\ExplorationAutomationService;

class TestExploration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:exploration {numberOfCharacters=25} {characterToIgnore?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests exploration with X number of characters';

    /**
     * Execute the console command.
     */
    public function handle(CharacterBuilder $characterBuilder, ExplorationAutomationService $explorationAutomationService) {
        ini_set('memory_limit','3G');

        $numberOfCharacters = $this->argument('numberOfCharacters');
        $characterToIgnore = $this->argument('characterToIgnore');

        $characters = $this->getTheCharacters($characterBuilder, $numberOfCharacters, $characterToIgnore);

        $this->line('Starting explorations for 1 hour, using default attack type, killing the first surface monster ...');
        
        foreach ($characters as $character) {
            $explorationAutomationService->beginAutomation($character, [
                'selected_monster_id'      => Monster::where('name', 'Sewer Rat')->first()->id,
                'auto_attack_length'       => 1,
                'attack_type'              => 'attack',
                'move_down_the_list_every' => null,
            ]);
        }

        $this->line('Automations have been started.');
    }

    /**
     * Get the collection of characters.
     * 
     * - Will create a specfic amount to match the number of characters we want to use for exploration.
     * - Will ignore a specfic character from the list to return.
     *
     * @param CharacterBuilder $characterBuilder
     * @param integer $numberOfCharacters
     * @param string|null $characterToIgnore
     * @return Collection
     */
    protected function getTheCharacters(CharacterBuilder $characterBuilder, int $numberOfCharacters, ?string $characterToIgnore): Collection {
        $characters = Character::query();

        if (!is_null($characterToIgnore)) {
            $characters = $characters->where('name', '!=', $characterToIgnore);
        }

        if ($numberOfCharacters > $characters->count()) {
            $charactersToCreate = $numberOfCharacters - $characters->count();

            $this->line('Creating character amount: ' . $charactersToCreate);

            $this->createTheCharacters($characterBuilder, $charactersToCreate);

            $this->line('Characters created.');

            return $this->getTheCharacters($characterBuilder, $numberOfCharacters, $characterToIgnore);

        }

        return $characters->take($numberOfCharacters)->get();
    }

    /**
     * Create the characters needed.
     *
     * @param CharacterBuilder $characterBuilder
     * @param integer $charactersToCreate
     * @return void
     */
    protected function createTheCharacters(CharacterBuilder $characterBuilder, int $charactersToCreate) {
        for ($i = 0; $i <= $charactersToCreate; $i++) {
            $user = $this->createUser();

            $surfaceMap = GameMap::where('name', 'Surface')->first();
            $gameClass  = GameClass::inRandomOrder()->first();
            $gameRace   = GameRace::inRandomOrder()->first();

            $this->createCharacter($characterBuilder, $user, $surfaceMap, $gameClass, $gameRace);

        }
    }

    /**
     * Create the user.
     *
     * @return User
     */
    protected function createUser(): User {
        return User::create([
            'email'            => Str::random(8) . '@test.com',
            'password'         => Hash::make(Str::random(8)),
            'ip_address'       => '0.0.0.' . rand(1, 100),
            'last_logged_in'   => now(),
            'guide_enabled'    => false
        ]);
    }

    /**
     * Create the character.
     *
     * @param CharacterBuilder $characterBuilder
     * @param GameMap $map
     * @param GameClass $class
     * @param Collection $races
     * @param string $password
     * @return Character
     * @throws Exception
     */
    protected function createCharacter(CharacterBuilder $characterBuilder, User $user, GameMap $map, GameClass $class, GameRace $race): Character {

        $characterBuilder->setRace($race)
            ->setClass($class)
            ->createCharacter($user, $map, Str::random(4) . str_replace(' ', '', $class->name))
            ->assignSkills()
            ->assignPassiveSkills()
            ->buildCharacterCache();

        return $characterBuilder->character();
    }
}
