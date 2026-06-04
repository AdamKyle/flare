<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Game\Automation\Services\ExplorationAutomationService;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
    public function handle(CharacterCreationPipeline $characterCreationPipeline, ExplorationAutomationService $explorationAutomationService)
    {
        ini_set('memory_limit', '3G');

        $numberOfCharacters = $this->argument('numberOfCharacters');
        $characterToIgnore = $this->argument('characterToIgnore');

        $characters = $this->getTheCharacters($characterCreationPipeline, $numberOfCharacters, $characterToIgnore);

        $this->line('Starting explorations for 1 hour, using default attack type, killing the first surface monster ...');

        $bar = $this->output->createProgressBar($characters->count());

        $bar->start();

        foreach ($characters as $character) {

            $explorationAutomationService->stopExploration($character);

            $explorationAutomationService->beginAutomation($character, [
                'selected_monster_id' => Monster::where('name', 'Sewer Rat')->first()->id,
                'auto_attack_length' => 1,
                'attack_type' => 'attack',
                'move_down_the_list_every' => null,
            ]);

            $bar->advance();
        }

        $bar->finish();

        $this->line('Automations have been started.');
    }

    /**
     * Get the collection of characters.
     *
     * - Will create a specific amount to match the number of characters we want to use for exploration.
     * - Will ignore a specific character from the list to return.
     */
    protected function getTheCharacters(CharacterCreationPipeline $characterCreationPipeline, int $numberOfCharacters, ?string $characterToIgnore): Collection
    {
        $characters = Character::query();

        if (! is_null($characterToIgnore)) {
            $characters = $characters->where('name', '!=', $characterToIgnore);
        }

        if ($numberOfCharacters > $characters->count()) {
            $charactersToCreate = $numberOfCharacters - $characters->count();

            $this->line('Creating character amount: '.$charactersToCreate);

            $this->createTheCharacters($characterCreationPipeline, $charactersToCreate);

            $this->line('Characters created.');

            return $this->getTheCharacters($characterCreationPipeline, $numberOfCharacters, $characterToIgnore);
        }

        return $characters->take($numberOfCharacters)->get();
    }

    /**
     * Create the characters needed.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function createTheCharacters(CharacterCreationPipeline $characterCreationPipeline, int $charactersToCreate)
    {
        for ($i = 0; $i <= $charactersToCreate; $i++) {
            $user = $this->createUser();

            $surfaceMap = GameMap::where('name', 'Surface')->first();
            $gameClass = GameClass::inRandomOrder()->first();
            $gameRace = GameRace::inRandomOrder()->first();

            $this->createCharacter($characterCreationPipeline, $user, $surfaceMap, $gameClass, $gameRace);
        }
    }

    /**
     * Create the user.
     */
    protected function createUser(): User
    {
        return User::create([
            'email' => Str::random(8).'@test.com',
            'password' => Hash::make(Str::random(8)),
            'ip_address' => '0.0.0.'.rand(1, 100),
            'last_logged_in' => now(),
            'guide_enabled' => false,
        ]);
    }

    /**
     * Create the character.
     *
     * @throws Exception
     */
    protected function createCharacter(CharacterCreationPipeline $characterCreationPipeline, User $user, GameMap $map, GameClass $class, GameRace $race): Character
    {
        $characterBuildState = new CharacterBuildState;
        $characterBuildState
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setMap($map)
            ->setCharacterName(Str::random(4).str_replace(' ', '', $class->name))
            ->setNow(now());

        $characterCreationPipeline->run($characterBuildState);

        return $characterBuildState->getCharacter();
    }
}
