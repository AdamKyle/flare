<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateTestCharacters extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-characters {password} {className?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates test characters to use for development';

    /**
     * Execute the console command.
     *
     * @param CharacterBuilder $characterBuilder
     * @return void
     * @throws Exception
     */
    public function handle(CharacterBuilder $characterBuilder): void {
        ini_set('memory_limit', -1);
        
        $password  = $this->argument('password');
        $className = $this->argument('className');
        $races     = GameRace::all();
        $map       = GameMap::where('default', true)->first();

        if (!is_null($className)) {

            $this->line('Ceating Maxed out character for class: ' . $className);

            $class = GameClass::where('name', $className)->first();

            if (is_null($class)) {
                $this->error('No class for name: ' . $className . ' found');

                return;
            }

            $character = $this->createCharacter($characterBuilder, $map, $class, $races, $password);

            $this->line('Created Character: ' . $character->name . ' who\'s email is: ' . $character->user->email);

            return;
        }

        $this->line('ATTN! This command will take a while as we create, level up and max out the characters for each class, including the characters with locked classes.');
        $this->line('This will also give each character 1 of every purgatory item (2 for shields, spells and rings) with proper enchantments (max level) and full holy stacks.');
        $this->line('');

        $gameClasses = GameClass::all();

        $progressBar = new ProgressBar(new ConsoleOutput(), $gameClasses->count());

        $headers = ['email', 'race', 'class'];
        $data    = [];

        $progressBar->start();

        foreach ($gameClasses as $class) {

            $character = $this->createCharacter($characterBuilder, $map, $class, $races, $password);

            $data[] = [
                $character->user->email,
                $character->race->name,
                $character->class->name,
            ];

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->line('');
        $this->line('Increasing Rank Fight To Max ....');
        $this->line('');

        Artisan::call('increase:rank-fight-to-max');

        $this->line('');
        $this->table($headers, $data);
    }

    /**
     * Create the user.
     *
     * @param string $password
     * @return User
     */
    protected function createUser(string $password): User {
        return User::create([
            'email'            => Str::random(8) . '@test.com',
            'password'         => Hash::make($password),
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
    protected function createCharacter(CharacterBuilder $characterBuilder, GameMap $map, GameClass $class, Collection $races, string $password): Character {
        $user = $this->createUser($password);
        $race = $races[rand(0, count($races) - 1)];

        $characterBuilder->setRace($race)
            ->setClass($class)
            ->createCharacter($user, $map, 'Test' . str_replace(' ', '', $class->name))
            ->assignSkills()
            ->assignPassiveSkills()
            ->buildCharacterCache();

        $character = $characterBuilder->character();

        Artisan::call('reincarnate:character ' . $character->name);

        Artisan::call('max-out:character ' . $character->name);

        return $character;
    }
}
