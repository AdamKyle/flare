<?php

namespace App\Game\ClassRanks\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use App\Game\ClassRanks\Values\ClassRankValue;
use Illuminate\Console\Command;

class AssignNewClassRanks extends Command
{
    protected $signature = 'assign:new-class-ranks';

    protected $description = 'Backfill missing CharacterClassRank rows for existing characters';

    public function __construct(private readonly CharacterBuilderService $characterBuilderService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameClasses = GameClass::all();

        $bar = $this->output->createProgressBar(Character::count());
        $bar->start();

        Character::chunkById(100, function ($characters) use ($gameClasses, $bar) {
            foreach ($characters as $character) {
                $existingClassIds = $character->classRanks()->pluck('game_class_id')->all();

                foreach ($gameClasses as $gameClass) {
                    if (in_array($gameClass->id, $existingClassIds)) {
                        continue;
                    }

                    $classRank = $character->classRanks()->create([
                        'character_id' => $character->id,
                        'game_class_id' => $gameClass->id,
                        'current_xp' => 0,
                        'required_xp' => ClassRankValue::XP_PER_LEVEL,
                        'level' => 0,
                    ]);

                    $this->characterBuilderService->assignWeaponMasteriesToClassRanks($classRank);
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }
}
