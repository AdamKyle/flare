<?php

namespace App\Console\Commands;

use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use Illuminate\Console\Command;

class LevelUpSkillsOnFakeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'level-skills:fake-users {amount} {skillId} {amountOfLevels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Levels characters skills based on how many characters.';

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
        $amount         = $this->argument('amount');
        $skillId        = $this->argument('skillId');
        $amountOfLevels = $this->argument('amountOfLevels');

        if ($amount <= 0) {
            $this->error('amount must be greator then 0.');
            return;
        }

        if ($amountOfLevels <= 0) {
            $this->error('amount of levels must be greator then 0.');
            return;
        }

        $this->info('Leveling character skills');

        $bar = $this->output->createProgressBar($amount);

        $bar->start();

        for ($i = 1; $i <= (int) $amount; $i++) {
            $character = Character::find($i);

            if (is_null($character)) {
                // We just don't care.
                continue;
            }

            $skill = $character->skills()->where('game_skill_id', $skillId)->first();

            if (is_null($skill)) {
                $this->error(' No skill was found for id: ' . $skillId . ' On character: ' . $character->id . '. Skill id should be the id of the Game Skill');
                return;
            }

            for ($j = 1; $j <= (int) $amountOfLevels; $j++) {
                $skill->refresh();
                
                if ($skill->can_train) {
                    $skill->update([
                        'currently_training' => true,
                        'xp_towards' => 0.10,
                        'xp' => 300,
                        'xp_max' => 150,
                    ]);
                } else {
                    $skill->update([
                        'xp' => 300,
                        'xp_max' => 200,
                    ]);
                }
    
                event(new UpdateSkillEvent($skill->refresh()));
            }
            
            $bar->advance();
        }

        $bar->finish();

        $this->info(' All Done :D');
    }
}
