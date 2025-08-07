<?php

namespace App\Console\AfterDeployment;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeWeaponTypesForClassRanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:weapon-types-for-class-ranks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Changes weapon types to use item types enum';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Character::chunkById(100, function ($characters) {
            foreach ($characters as $character) {
                $this->changeClassRanksMasteries($character);
            }
        });
    }

    private function changeClassRanksMasteries(Character $character): void
    {
        $bulkInsertData = [];

        foreach ($character->classRanks as $classRank) {
            $weaponMasteries = $classRank->weaponMasteries;

            // Batch update weapon types instead of calling update() multiple times
            $updates = [];
            foreach ($weaponMasteries as $weaponMastery) {
                $newType = $this->getNewWeaponType($weaponMastery);
                if ($newType !== null && $weaponMastery->weapon_type !== $newType) {
                    $updates[] = [
                        'id' => $weaponMastery->id,
                        'weapon_type' => $newType,
                    ];
                }
            }

            // Perform a batch update if there are changes
            if (!empty($updates)) {
                DB::transaction(function () use ($updates) {
                    foreach ($updates as $update) {
                        CharacterClassRankWeaponMastery::where('id', $update['id'])->update(['weapon_type' => $update['weapon_type']]);
                    }
                });
            }

            // Prepare bulk insert data
            $missingTypes = [
                ItemType::WAND->value,
                ItemType::CENSER->value,
                ItemType::SWORD->value,
                ItemType::CLAW->value,
            ];

            foreach ($missingTypes as $weaponType) {
                $bulkInsertData[] = [
                    'character_class_rank_id' => $classRank->id,
                    'weapon_type' => $weaponType,
                    'current_xp' => 0,
                    'required_xp' => 1000000,
                    'level' => 0,
                ];
            }
        }

        // Perform a bulk insert instead of individual insert calls
        if (!empty($bulkInsertData)) {
            CharacterClassRankWeaponMastery::insert($bulkInsertData);
        }
    }

    private function getNewWeaponType(CharacterClassRankWeaponMastery $characterClassRankWeaponMastery): ?string
    {
        static $weaponTypeMap = [
            'isWeapon' => ItemType::DAGGER->value,
            'isBow' => ItemType::BOW->value,
            'isFan' => ItemType::FAN->value,
            'isGun' => ItemType::GUN->value,
            'isHammer' => ItemType::HAMMER->value,
            'isMace' => ItemType::MACE->value,
            'isDamageSpell' => ItemType::SPELL_DAMAGE->value,
            'isHealingSpell' => ItemType::SPELL_HEALING->value,
            'isScratchAwl' => ItemType::SCRATCH_AWL->value,
            'isStaff' => ItemType::STAVE->value,
        ];

        $weaponType = new WeaponMasteryValue((int) $characterClassRankWeaponMastery->weapon_type);

        foreach ($weaponTypeMap as $method => $itemType) {
            if (method_exists($weaponType, $method) && $weaponType->$method()) {
                return $itemType;
            }
        }

        return null;
    }
}
