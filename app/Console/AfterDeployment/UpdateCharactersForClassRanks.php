<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\GameClass;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Illuminate\Console\Command;

class UpdateCharactersForClassRanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:class-ranks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns Class Ranks to Characters';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $gameClasses = GameClass::all();

        $bar = $this->output->createProgressBar(Character::count());
        $bar->start();

        Character::chunkById(100, function($characters) use($gameClasses, $bar) {
            foreach ($characters as $character) {
                foreach ($gameClasses as $gameClass) {
                    $classRank    = $character->classRanks()->where('game_class_id', $gameClass->id)->first();
                    $hasGameClass = !is_null($classRank);

                    if ($hasGameClass) {
                        $this->assignWeaponMasteriesToClassRanks($classRank);

                        continue;
                    }

                    $classRank = $character->classRanks()->create([
                        'character_id'   => $character->id,
                        'game_class_id'  => $gameClass->id,
                        'current_xp'     => 0,
                        'required_xp'    => ClassRankValue::XP_PER_LEVEL,
                        'level'          => 0,
                    ]);

                    $this->assignWeaponMasteriesToClassRanks($classRank);
                }

                $bar->advance();
            }
        });

        $bar->finish();
    }

    protected function assignWeaponMasteriesToClassRanks(CharacterClassRank $classRank): void {
        foreach (WeaponMasteryValue::getTypes() as $type) {

            $foundMastery = $classRank->weaponMasteries()->where('weapon_type', $type)->first();

            if (!is_null($foundMastery)) {
                continue;
            }

            $classRank->weaponMasteries()->create([
                'character_class_rank_id'   => $classRank->id,
                'weapon_type'               => $type,
                'current_xp'                => 0,
                'required_xp'               => WeaponMasteryValue::XP_PER_LEVEL,
                'level'                     => $this->getDefaultLevel($classRank, $type),
            ]);
        }
    }

    protected function getDefaultLevel(CharacterClassRank $classRank, int $type) {
        if (($classRank->gameClass->type()->isFighter() ||
             $classRank->gameClass->type()->isThief() ||
             $classRank->gameClass->type()->isVampire() ||
             $classRank->gameClass->type()->isPrisoner() ||
             $classRank->gameClass->type()->isBlackSmith()) && (new WeaponMasteryValue($type))->isWeapon())
        {
            return 5;
        }

        if (($classRank->gameClass->type()->isHeretic() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isStaff()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isHeretic() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isDamageSpell()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isProphet()) && (new WeaponMasteryValue($type))->isHealingSpell()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isRanger() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isHealingSpell()) {
            return 2;
        }

        if (($classRank->gameClass->type()->isRanger()) && (new WeaponMasteryValue($type))->isBow()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isThief() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isBow()) {
            return 2;
        }

        if (($classRank->gameClass->type()->isBlackSmith()) && (new WeaponMasteryValue($type))->isHammer()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isMerchant()) && (new WeaponMasteryValue($type))->isBow()) {
            return 3;
        }

        if (($classRank->gameClass->type()->isMerchant()) && (new WeaponMasteryValue($type))->isStaff()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isGunslinger()) && (new WeaponMasteryValue($type))->isGun()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isDancer()) && (new WeaponMasteryValue($type))->isFan()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isCleric()) && (new WeaponMasteryValue($type))->isMace()) {
            return 5;
        }

        return 0;
    }
}
