<?php

namespace App\Console\Commands;

use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\Item;
use App\Flare\Models\Notification;
use App\Flare\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Tests\Setup\CharacterSetup;

class CreateFakeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:fake-users {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a set of fake users for the system.';

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
        $amount = $this->argument('amount');

        if ($amount <= 0) {
            $this->error('amount must be greator then 0.');
            return;
        }

        $this->info('Creating ' . $amount . ' characters.');

        $bar = $this->output->createProgressBar($amount);
        $bar->start();

        for ($i = 1; $i <= (int) $this->argument('amount'); $i++) {
            $user = User::factory()->create([
                'is_test' => true,
            ]);

            $race  = GameRace::inRandomOrder()->first();
            $class = GameClass::inRandomOrder()->first();
            $map   = GameMap::where('default', true)->first(); 

            (new CharacterBuilder)->setRace($race)
                                  ->setClass($class)
                                  ->createCharacter($user, $map, Str::random(10))
                                  ->assignSkills();

            $bar->advance();
        }

        $bar->finish();

        $this->info(' All Done :D');
    }
}
