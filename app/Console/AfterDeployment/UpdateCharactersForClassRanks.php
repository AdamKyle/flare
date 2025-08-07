<?php

namespace App\Console\AfterDeployment;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\GameClass;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\ClassRanks\Values\ClassRankValue;
use Illuminate\Console\Command;

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

                        $classRank->load('weaponMasteries', 'gameClass');
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
        $existing = [];
        foreach ($classRank->weaponMasteries as $mastery) {
            $existing[$mastery->weapon_type] = $mastery;
        }

        $className = strtolower(trim($classRank->gameClass->name));
        $preferred = ItemTypeMapping::getForClass($className);
        $preferred = is_array($preferred) ? $preferred : ($preferred ? [$preferred] : []);
        $firstPreferred = $preferred[0] ?? null;

        $allTypes = ItemType::allWeaponTypes();
        $misaligned = $classRank->weaponMasteries->whereNotIn('weapon_type', $allTypes)->values();
        $misalignedIndex = 0;

        foreach ($allTypes as $type) {
            if (isset($existing[$type])) {
                continue;
            }

            if ($misalignedIndex < $misaligned->count()) {
                $misaligned->get($misalignedIndex)->update(['weapon_type' => $type]);
                $misalignedIndex++;
                continue;
            }

            $level = 0;
            if ($firstPreferred && $firstPreferred === $type) {
                $level = 5;
            } elseif (in_array($type, $preferred)) {
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
