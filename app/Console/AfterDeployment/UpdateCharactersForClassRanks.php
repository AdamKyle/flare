<?php

namespace App\Console\AfterDeployment;

use Illuminate\Console\Command;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\GameClass;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\ClassRanks\Values\ClassRankValue;

class UpdateCharactersForClassRanks extends Command
{
    protected $signature = 'assign:class-ranks';

    protected $description = 'Assigns Class Ranks to Characters';

    public function handle(): void
    {
        $gameClasses = GameClass::all()->keyBy('id');

        $bar = $this->output->createProgressBar(Character::count());
        $bar->start();

        Character::with(['classRanks.weaponMasteries', 'classRanks.gameClass'])->chunkById(100, function ($characters) use ($gameClasses, $bar) {
            foreach ($characters as $character) {
                foreach ($gameClasses as $gameClass) {
                    $classRank = $character->classRanks->firstWhere('game_class_id', $gameClass->id);

                    if (!$classRank) {
                        $classRank = $character->classRanks()->create([
                            'character_id' => $character->id,
                            'game_class_id' => $gameClass->id,
                            'current_xp' => 0,
                            'required_xp' => ClassRankValue::XP_PER_LEVEL,
                            'level' => 0,
                        ]);

                        $classRank->load('weaponMasteries');
                    }

                    $this->syncWeaponMasteries($classRank);
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }

    protected function syncWeaponMasteries(CharacterClassRank $classRank): void
    {
        $existingMasteries = $classRank->weaponMasteries->keyBy('weapon_type');

        $className = strtolower(trim($classRank->gameClass->name));
        $preferredTypes = ItemTypeMapping::getForClass($className);
        $preferredTypes = is_array($preferredTypes) ? $preferredTypes : ($preferredTypes ? [$preferredTypes] : []);
        $firstPreferred = $preferredTypes[0] ?? null;

        $allTypes = ItemType::allWeaponTypes();

        foreach ($allTypes as $type) {
            $existing = $classRank->weaponMasteries->firstWhere('weapon_type', $type);

            if ($existing) {
                continue;
            }

            $misaligned = $classRank->weaponMasteries->first(fn($m) => !in_array($m->weapon_type, $allTypes));
            if ($misaligned) {
                $misaligned->update(['weapon_type' => $type]);
                continue;
            }

            $level = 0;
            if ($firstPreferred && $firstPreferred === $type) {
                $level = 5;
            } elseif (in_array($type, $preferredTypes)) {
                $level = 2;
            }

            $classRank->weaponMasteries()->create([
                'character_class_rank_id' => $classRank->id,
                'weapon_type' => $type,
                'current_xp' => $level * ClassRankValue::XP_PER_LEVEL,
                'required_xp' => ClassRankValue::XP_PER_LEVEL,
                'level' => $level,
            ]);
        }
    }
}
