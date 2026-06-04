<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateCharacter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:character {email} {characterName} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a character';

    /**
     * Execute the console command.
     */
    public function handle(CharacterCreationPipeline $characterCreationPipeline)
    {
        $map = GameMap::where('default', true)->first();

        $race = $this->choice('Which Race?', GameRace::all()->pluck('name')->toArray());
        $class = $this->choice('Which Class?', GameClass::all()->pluck('name')->toArray());

        $race = GameRace::where('name', $race)->first();
        $class = GameClass::where('name', $class)->first();

        $user = User::create([
            'email' => $this->argument('email'),
            'password' => Hash::make($this->argument('password')),
            'ip_address' => '127.0.0.1',
            'last_logged_in' => now(),
            'guide_enabled' => true,
        ]);

        event(new Registered($user));

        $characterBuildState = new CharacterBuildState;
        $characterBuildState
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setMap($map)
            ->setCharacterName($this->argument('characterName'))
            ->setNow(now());

        $characterCreationPipeline->run($characterBuildState);

        $this->line('Character '.$this->argument('characterName').' has been created!');
    }
}
