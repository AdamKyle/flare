<?php

namespace App\Console\Commands;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\PassiveSkill;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Illuminate\Console\Command;
use App\Flare\Models\Character;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UnlockBuildingForPassiveName extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unlock-building:for-passive {passiveName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlocks a building if the characters passive for that building is at the appropriate level.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer) {
        $bar = $this->output->createProgressBar(Character::count());

        Character::chunkById(100, function($characters) use ($manager, $kingdomTransformer, $bar) {
            foreach ($characters as $character) {
                $passive = $character->passiveSkills->where('passive_skill_id', PassiveSkill::where('name', $this->argument('passiveName'))->first()->id)->first();

                if (is_null($passive)) {
                    $this->line('No passive found for that name.');

                    return;
                }

                if (!$passive->passiveSkill->passiveType()->unlocksBuilding()) {
                    $this->line('Passive does not unlock building.');

                    return;
                }

                $kingdoms     = $character->kingdoms;
                $gameBuilding = GameBuilding::where('name', $passive->passiveSkill->name)->first();

                if ($gameBuilding->level_required !== $passive->current_level) {

                    $bar->advance();

                    continue;
                }

                foreach ($kingdoms as $kingdom) {

                    $kingdom->buildings()->where('game_building_id', $gameBuilding->id)->update([
                        'is_locked' => false,
                    ]);

                    $kingdom  = new Item($kingdom->refresh(), $kingdomTransformer);
                    $kingdom  = $manager->createData($kingdom)->toArray();
                    $user     = $character->user;

                    event(new UpdateKingdom($user, $kingdom));
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }
}
