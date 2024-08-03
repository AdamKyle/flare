<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use Illuminate\Console\Command;

class AssignNewBuildingsToExistingKingdoms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:new-buildings-to-existing-kingdoms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns new buildings to all existing kingdoms.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Kingdom::chunkById(150, function ($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $this->assignNewBuildingsToExistingKingdoms($kingdom);
            }
        });
    }

    private function assignNewBuildingsToExistingKingdoms(Kingdom $kingdom): void
    {
        $character = $kingdom->character;

        foreach (GameBuilding::all() as $building) {

            $foundBuilding = $kingdom->buildings()->where('game_building_id', $building->id)->first();

            if (! is_null($foundBuilding)) {
                continue;
            }

            $isLocked = $building->is_locked;

            if ($isLocked) {

                if (! is_null($character)) {
                    $passive = $character->passiveSkills()->where('passive_skill_id', $building->passive_skill_id)->first();

                    if (! is_null($passive)) {
                        $isLocked = ! ($passive->current_level >= $building->level_required); // We're not locked if we are at or above the level
                    }
                }

            }

            $kingdom->buildings()->create([
                'game_building_id' => $building->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'current_defence' => $building->base_defence,
                'current_durability' => $building->base_durability,
                'max_defence' => $building->base_defence,
                'max_durability' => $building->base_durability,
                'is_locked' => $isLocked,
            ]);
        }
    }
}
