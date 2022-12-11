<?php

namespace App\Game\ClassRanks\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Character;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class ClassRankService {

    use FetchEquipped;

    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    /**
     * give xp to a class rank for the characters current class.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function giveXpToClassRank(Character $character): void {
        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        if (is_null($classRank)) {
            throw new Exception('No Class Rank Found for character: ' . $character->name . ' for id: ' . $character->ghame_class_id);
        }

        if ($classRank->level >= ClassRankValue::MAX_LEVEL) {
            return;
        }

        $classRank->update([
            'current_xp' => $classRank->current_xp + ClassRankValue::XP_PER_KILL,
        ]);

        $classRank = $classRank->refresh();

        if ($classRank->current_xp >= $classRank->required_xp) {
             $classRank->update([
                 'level'      => $classRank->level + 1,
                 'current_xp' => 0,
             ]);

             event(new ServerMessageEvent('You gained a new class rank in: ' . $character->class->name));
        }
    }

    /**
     * Give XP to equipped specials.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function giveXpToEquippedClassSpecialties(Character $character): void {
        $equippedSpecials = $character->classSpecialsEquipped()->where('equipped', true)->get();

        if ($equippedSpecials->isEmpty()) {
            return;
        }

        foreach ($equippedSpecials as $special) {
            if ($special->level >= ClassSpecialValue::MAX_LEVEL) {
                return;
            }

            $special->update([
                'current_xp' => $special->current_xp + ClassSpecialValue::XP_PER_KILL,
            ]);

            $special = $special->refresh();

            if ($special->current_xp >= $special->required_xp) {
                $special->update([
                    'level'      => $special->level + 1,
                    'current_xp' => 0,
                ]);

                event(new ServerMessageEvent('Your class special:  ' . $special->gameClassSpecial->name . ' has gamed a new level is now level: ' . $special->level));

                $this->updateCharacterAttackTypes->updateCache($character->refresh());
            }
        }

    }

    /**
     * Give XP to all applicable weapon masteries for the current class.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function giveXpToMasteries(Character $character) {
        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        if (is_null($classRank)) {
            throw new Exception('No Class Rank Found for character: ' . $character->name . ' for id: ' . $character->ghame_class_id);
        }

        $inventory = $this->fetchEquipped($character);

        foreach (WeaponMasteryValue::getTypes() as $type) {

            $inventorySlot = $inventory->where('item.type', WeaponMasteryValue::getTypeForNumericalValue($type))->first();

            if (!is_null($inventorySlot)) {
                $weaponMastery = $classRank->weaponMasteries()->where('weapon_type', $type)->first();

                if ($weaponMastery->level >= WeaponMasteryValue::MAX_LEVEL) {
                    continue;
                }

                $weaponMastery->update([
                    'current_xp' => $weaponMastery->current_xp + WeaponMasteryValue::XP_PER_KILL,
                ]);

                $weaponMastery = $weaponMastery->refresh();

                if ($weaponMastery->current_xp >= $weaponMastery->required_xp) {
                    $weaponMastery->update([
                        'level'      => $weaponMastery->level + 1,
                        'current_xp' => 0,
                    ]);

                    $weaponMastery = $weaponMastery->refresh();

                    event(new ServerMessageEvent($character->user,'Your class: ' .
                        $classRank->gameClass->name . ' has gained a new level in (Weapon Masteries): ' .
                        (new WeaponMasteryValue($type))->getName() .
                        ' and is now level: ' . $weaponMastery->level
                    ));
                }
            }
        }
    }
}
