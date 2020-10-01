<?php

namespace App\Flare\Console\Commands;

use Hash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use App\Flare\Mail\GeneratedAdmin;
use App\Flare\Models\Character;
use App\Flare\Values\BaseSkillValue;
use App\Flare\Models\User;
use DB;

class UpdateCharacterSkills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:character:skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the character skills when the config is changed.';

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
        $bar = $this->output->createProgressBar(DB::table('characters')->count());

        $bar->start();
        $this->line('');
        Character::with('skills')->orderBy('characters.id')->chunk(100, function ($characters) use ($bar) {
            foreach ($characters as $character) {
                foreach (config('game.skills') as $skill) {
                    if ($character->skills()->where('name', $skill['name'])->get()->isEmpty()) {
                        $character->skills()->create(
                            resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($character, $skill)
                        );
                        
                        $this->info('Added Skill: ' . $skill['name'] . ' to: ' . $character->name);
                    }
                }
                $this->line('');
                $bar->advance();
            }
        });

        $bar->finish();
        $this->line('');
        $this->info('Updated characters with all skills currently in config.');
    }
}
