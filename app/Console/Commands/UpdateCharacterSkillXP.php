<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class UpdateCharacterSkillXP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:character-skill-xp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all the characters skill XP';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $progressBar = new ProgressBar(new ConsoleOutput(), DB::table('characters')->count());

        Character::chunkById(100, function($characters) use ($progressBar) {
           foreach ($characters as $character) {
               $skills = $character->skills()->whereIn('game_skill_id', $this->getTrainableSkillIdS())->get();

               $this->changeSkillXP($skills);

               $progressBar->advance();
           }
        });

        $progressBar->finish();
    }

    protected function getTrainableSkillIdS(): array {
        return GameSkill::where('type', SkillTypeValue::TRAINING)->pluck('id')->toArray();
    }

    protected function changeSkillXP(Collection $skills): void {
        foreach ($skills as $skill) {

            if ($skill->level === $skill->baseSkill->max_level) {
                $skill->update([
                    'xp_max' => $skill->level * 10,
                ]);

                continue;
            }

            $skill->update([
                'xp_max' => $skill->level * 10,
            ]);

            $skill = $skill->refresh();

            if ($skill->xp >= $skill->xp_max) {
                $level = $skill->level + 1;

                $skill->update([
                    'level'              => $level,
                    'xp_max'             => $level * 10,
                    'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                    'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                    'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                    'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                    'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                    'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                    'xp'                 => 0,
                ]);
            }
        }
    }
}
