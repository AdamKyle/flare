<?php

namespace App\Console\AfterDeployment;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\CharacterClassRank;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FixWeaponMasteryTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:weapon-mastery-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes weapon mastery types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bar = $this->output->createProgressBar(CharacterClassRank::count());
        $bar->start();

        CharacterClassRank::with('gameClass')->chunkById(100, function ($characterClassRanks) use ($bar) {
            $now = Carbon::now();

            DB::transaction(function () use ($characterClassRanks, $now, $bar) {
                $deleteIds = $characterClassRanks->pluck('id')->all();

                DB::table('character_class_ranks_weapon_masteries')->whereIn('character_class_rank_id', $deleteIds)->delete();

                $inserts = [];

                foreach ($characterClassRanks as $rank) {
                    foreach (ItemType::allWeaponTypes() as $type) {
                        $inserts[] = [
                            'character_class_rank_id' => $rank->id,
                            'weapon_type'             => $type,
                            'current_xp'              => 0,
                            'required_xp'             => WeaponMasteryValue::XP_PER_LEVEL,
                            'level'                   => $this->getDefaultLevel($rank, $type),
                            'created_at'              => $now,
                            'updated_at'              => $now,
                        ];
                    }

                    $bar->advance();
                }

                DB::table('character_class_ranks_weapon_masteries')->insert($inserts);
            });
        });

        $bar->finish();
    }

    protected function assignWeaponMasteriesToClassRanks(CharacterClassRank $classRank): void
    {
        foreach (ItemType::allWeaponTypes() as $type) {
            $classRank->weaponMasteries()->create([
                'character_class_rank_id' => $classRank->id,
                'weapon_type' => $type,
                'current_xp' => 0,
                'required_xp' => WeaponMasteryValue::XP_PER_LEVEL,
                'level' => $this->getDefaultLevel($classRank, $type),
            ]);
        }
    }

    /**
     * Get default level for weapon mastery.
     *
     * @return int
     *
     * @throws Exception
     */
    protected function getDefaultLevel(CharacterClassRank $classRank, string $type)
    {
        $mapping = ItemTypeMapping::getForClass(
            $classRank->gameClass->name
        );

        if (is_null($mapping)) {
            return 0;
        }

        if (is_string($mapping)) {
            return $type === $mapping
                ? 5
                : 0;
        }

        $pos = array_search(
            $type,
            $mapping,
            true
        );

        if ($pos === false) {
            return 0;
        }

        $classType = $classRank->gameClass->type();

        if ($classType->isPrisoner()) {
            return $pos === 0
                ? 5
                : 0;
        }

        if ($classType->isMerchant()) {
            return $pos === 0
                ? 2
                : 3;
        }

        return 5;
    }
}
