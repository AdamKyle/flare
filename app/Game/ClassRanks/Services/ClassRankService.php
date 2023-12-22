<?php

namespace App\Game\ClassRanks\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\GameClassSpecial;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class ClassRankService {

    use FetchEquipped, ResponseBuilder;

    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    /**
     * Get the class specials for the character.
     *
     * @param Character $character
     * @return array
     */
    public function getSpecials(Character $character): array {
        $classSpecialsEquipped    = $character->classSpecialsEquipped()->with('gameClassSpecial')->where('equipped', '=', true)->get();
        $classSpecialsNotEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial')->where('equipped', '=', false)->get();

        return [
            'class_specialties' => GameClassSpecial::all()->transform(function($special) {
                $special->class_name = $special->gameClass->name;

                return $special;
            }),
            'specials_equipped' => array_values($classSpecialsEquipped->transform(function($specialEquipped) {
                $specialEquipped->class_name = $specialEquipped->gameClassSpecial->gameClass->name;

                return $specialEquipped;
            })->toArray()),
            'class_ranks'          => $character->classRanks->toArray(),
            'other_class_specials' => array_values($classSpecialsNotEquipped->transform(function($special) {
                $special->class_name = $special->gameClassSpecial->gameClass->name;

                return $special;
            })->toArray()),
        ];
    }

    /**
     * Get class ranks.
     *
     * @param Character $character
     * @return array
     */
    public function getClassRanks(Character $character): array {
        $classRanks = $character->classRanks()->with(['gameClass', 'weaponMasteries'])->get();

        $classRanks  = $classRanks->transform(function($classRank) use($character) {

            $classRank->class_name = $classRank->gameClass->name;

            $classRank->is_active  = $classRank->gameClass->id === $character->game_class_id;

            $classRank->is_locked  = $this->isClassLocked($character, $classRank);

            $classRank->weapon_masteries = $classRank->weaponMasteries->transform(function($weaponMastery) {

                $weaponMastery->mastery_name = (new WeaponMasteryValue($weaponMastery->weapon_type))->getName();

                return $weaponMastery;
            });

            $primaryClass   = $classRank->gameClass->primaryClassRequired;
            $secondaryClass = $classRank->gameClass->secondaryClassRequired;

            $classRank->primary_class_name             = !is_null($primaryClass) ? $primaryClass->name : null;
            $classRank->secondary_class_name           = !is_null($secondaryClass) ? $secondaryClass->name : null;
            $classRank->primary_class_required_level   = $classRank->gameClass->primary_required_class_level;
            $classRank->secondary_class_required_level = $classRank->gameClass->secondary_required_class_level;

            return $classRank;
        })->sortByDesc(function($item) {
            return $item->is_active;
        })->all();

        return $this->successResult([
            'class_ranks' => array_values($classRanks)
        ]);
    }

    /**
     * Equip a class specialty
     *
     * @param Character $character
     * @param GameClassSpecial $gameClassSpecial
     * @return array
     * @throws Exception
     */
    public function equipSpecialty(Character $character, GameClassSpecial $gameClassSpecial): array {
        if ($character->classSpecialsEquipped->where('equipped', true)->count() >= 3) {
            return $this->errorResult('You have the maximum amount of specials (3) equipped. You cannot equip anymore.');
        }

        if ($gameClassSpecial->specialty_damage > 0) {
            if ($character->classSpecialsEquipped->where('gameClassSpecial.specialty_damage', '>', 0)->where('equipped', true)->count() > 0) {
                return $this->errorResult('You already have a damage specialty equipped and cannot equip another one.');
            }
        }

        $classRank = $character->classRanks->where('game_class_id', $character->game_class_id)->first();

        if ($classRank->level < $gameClassSpecial->requires_class_rank_level) {
            return $this->errorResult('You do not have the required class rank level for this.');
        }

        $classSpecial = $character->classSpecialsEquipped->where('game_class_special_id', $gameClassSpecial->id)
            ->where('character_id', $character->id)
            ->where('equipped', false)
            ->first();

        if (!is_null($classSpecial)) {
            $classSpecial->update([
                'equipped' => true,
            ]);
        } else {
            $character->classSpecialsEquipped()->create([
                'character_id'           => $character->id,
                'game_class_special_id'  => $gameClassSpecial->id,
                'level'                  => 1,
                'current_xp'             => 0,
                'required_xp'            => ClassSpecialValue::XP_PER_LEVEL,
                'equipped'               => true,
            ]);
        }

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        return $this->successResult(array_merge([
            'message' => 'Equipped class special: ' . $gameClassSpecial->name
        ], $this->getSpecials($character)));
    }

    /**
     * Unequip the specialty.
     *
     * @param Character $character
     * @param CharacterClassSpecialtiesEquipped $classSpecialEquipped
     * @return array
     * @throws Exception
     */
    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped) {
        $specialEquipped = $character->classSpecialsEquipped()->where('id', $classSpecialEquipped->id)->first();

        if (is_null($specialEquipped)) {
            return $this->errorResult('You do not own that.');
        }

        $specialEquipped->update(['equipped' => false]);

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        return $this->successResult(array_merge([
            'message' => 'Unequipped class special: ' . $classSpecialEquipped->gameClassSpecial->name
        ], $this->getSpecials($character)));
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

             event(new ServerMessageEvent($character->user,'You gained a new class rank in: ' . $character->class->name));
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

        foreach ($equippedSpecials as $special) {
            if ($special->level >= ClassSpecialValue::MAX_LEVEL) {
                continue;
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

                event(new ServerMessageEvent($character->user,'Your class special:  ' . $special->gameClassSpecial->name . ' has gained a new level is now level: ' . $special->level));

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
    public function giveXpToMasteries(Character $character): void {
        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        $inventory = $this->fetchEquipped($character);

        if (is_null($inventory)) {
            return;
        }

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

                    $this->updateCharacterAttackTypes->updateCache($character->refresh());

                    event(new ServerMessageEvent($character->user,'Your class: ' .
                        $classRank->gameClass->name . ' has gained a new level in (Weapon Masteries): ' .
                        (new WeaponMasteryValue($type))->getName() .
                        ' and is now level: ' . $weaponMastery->level
                    ));
                }
            }
        }
    }

    protected function isClassLocked(Character $character, CharacterClassRank $classRank): bool {
        if (!is_null($classRank->gameClass->primary_required_class_id) &&
            !is_null($classRank->gameClass->secondary_required_class_id)) {

            $primaryRequiredClassId   = $classRank->gameClass->primary_required_class_id;
            $secondaryRequiredClassId = $classRank->gameClass->secondary_required_class_id;

            $primaryClassRank   = $character->classRanks->where('game_class_id', $primaryRequiredClassId)->first();
            $secondaryClassRank = $character->classRanks->where('game_class_id', $secondaryRequiredClassId)->first();


            return !(($primaryClassRank->level >= $classRank->gameClass->primary_required_class_level) &&
                   ($secondaryClassRank->level >= $classRank->gameClass->secondary_required_class_level));
        }

        return false;
    }
}
