<?php

namespace App\Game\ClassRanks\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\GameClassSpecial;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\ClassRanksMessageTypes;
use Exception;

class ClassRankService
{
    use FetchEquipped, ResponseBuilder;

    public function __construct(private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes, private BattleMessageHandler $battleMessageHandler) {}

    /**
     * Get the class specials for the character.
     */
    public function getSpecials(Character $character): array
    {
        $classSpecialsEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial')->where('equipped', '=', true)->get();
        $classSpecialsNotEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial')->where('equipped', '=', false)->get();

        return [
            'class_specialties' => GameClassSpecial::all()->transform(function ($special) {
                $special->class_name = $special->gameClass->name;

                return $special;
            }),
            'specials_equipped' => array_values($classSpecialsEquipped->transform(function ($specialEquipped) {
                $specialEquipped->class_name = $specialEquipped->gameClassSpecial->gameClass->name;

                return $specialEquipped;
            })->toArray()),
            'class_ranks' => $character->classRanks->toArray(),
            'other_class_specials' => array_values($classSpecialsNotEquipped->transform(function ($special) {
                $special->class_name = $special->gameClassSpecial->gameClass->name;

                return $special;
            })->toArray()),
        ];
    }

    /**
     * Get class ranks.
     */
    public function getClassRanks(Character $character): array
    {
        $classRanks = $character->classRanks()->with(['gameClass', 'weaponMasteries'])->get();

        $classRanks = $classRanks->transform(function ($classRank) use ($character) {
            $classRank->class_name = $classRank->gameClass->name;
            $classRank->is_active = $classRank->gameClass->id === $character->game_class_id;
            $classRank->is_locked = $this->isClassLocked($character, $classRank);

            $preferredType = ItemTypeMapping::getForClass($classRank->gameClass->name);

            $sortedWeaponMasteries = [];

            $tempMasteries = $classRank->weaponMasteries->all();

            usort($tempMasteries, function ($a, $b) use ($preferredType) {
                $typeA = $a->weapon_type;
                $typeB = $b->weapon_type;

                if (is_null($preferredType)) {
                    return 0;
                }

                if (is_array($preferredType)) {
                    $indexA = array_search($typeA, $preferredType);
                    $indexB = array_search($typeB, $preferredType);

                    $indexA = $indexA === false ? PHP_INT_MAX : $indexA;
                    $indexB = $indexB === false ? PHP_INT_MAX : $indexB;

                    return $indexA <=> $indexB;
                }

                // Single preferred type
                if ($typeA === $preferredType && $typeB !== $preferredType) {
                    return -1;
                }
                if ($typeB === $preferredType && $typeA !== $preferredType) {
                    return 1;
                }

                return 0;
            });

            foreach ($tempMasteries as $mastery) {
                $masteryData = [
                    'id' => $mastery->id,
                    'character_class_rank_id' => $mastery->character_class_rank_id,
                    'weapon_type' => $mastery->weapon_type,
                    'current_xp' => $mastery->current_xp,
                    'required_xp' => $mastery->required_xp,
                    'mastery_name' => ucwords(str_replace('-', ' ', $mastery->weapon_type)),
                    'level' => $mastery->level,
                ];

                $sortedWeaponMasteries[] = $masteryData;
            }

            $classRank->weapon_masteries = $sortedWeaponMasteries;


            $primaryClass = $classRank->gameClass->primaryClassRequired;
            $secondaryClass = $classRank->gameClass->secondaryClassRequired;

            $classRank->primary_class_name = !is_null($primaryClass) ? $primaryClass->name : null;
            $classRank->secondary_class_name = !is_null($secondaryClass) ? $secondaryClass->name : null;
            $classRank->primary_class_required_level = $classRank->gameClass->primary_required_class_level;
            $classRank->secondary_class_required_level = $classRank->gameClass->secondary_required_class_level;

            return $classRank;
        })->sortByDesc(fn($item) => $item->is_active)
            ->values();

        $result = [];

        foreach ($classRanks as $rank) {
            $rankData = $rank->toArray();
            $rankData['weapon_masteries'] = $rank->weapon_masteries;
            $result[] = $rankData;
        }

        return $this->successResult([
            'class_ranks' => $result,
        ]);
    }


    /**
     * Equip a class specialty
     *
     * @throws Exception
     */
    public function equipSpecialty(Character $character, GameClassSpecial $gameClassSpecial): array
    {
        if ($character->classSpecialsEquipped->where('equipped', true)->count() >= 3) {
            return $this->errorResult('You have the maximum amount of specials (3) equipped. You cannot equip anymore.');
        }

        if ($gameClassSpecial->specialty_damage > 0) {
            if ($character->classSpecialsEquipped->where('gameClassSpecial.specialty_damage', '>', 0)->where('equipped', true)->count() > 0) {
                return $this->errorResult('You already have a damage specialty equipped and cannot equip another one.');
            }
        }

        $classRank = $character->classRanks->where('game_class_id', $gameClassSpecial->game_class_id)->first();

        if ($classRank->level < $gameClassSpecial->requires_class_rank_level) {
            return $this->errorResult('You do not have the required class rank level for this.');
        }

        $classSpecial = $character->classSpecialsEquipped->where('game_class_special_id', $gameClassSpecial->id)
            ->where('character_id', $character->id)
            ->where('equipped', false)
            ->first();

        if (! is_null($classSpecial)) {
            $classSpecial->update([
                'equipped' => true,
            ]);
        } else {
            $character->classSpecialsEquipped()->create([
                'character_id' => $character->id,
                'game_class_special_id' => $gameClassSpecial->id,
                'level' => 1,
                'current_xp' => 0,
                'required_xp' => ClassSpecialValue::XP_PER_LEVEL,
                'equipped' => true,
            ]);
        }

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        event(new UpdateTopBarEvent($character));

        return $this->successResult(array_merge([
            'message' => 'Equipped class special: ' . $gameClassSpecial->name,
        ], $this->getSpecials($character)));
    }

    /**
     * Unequip the specialty.
     *
     * @return array
     *
     * @throws Exception
     */
    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped)
    {
        $specialEquipped = $character->classSpecialsEquipped()->where('id', $classSpecialEquipped->id)->first();

        if (is_null($specialEquipped)) {
            return $this->errorResult('You do not own that.');
        }

        $specialEquipped->update(['equipped' => false]);

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        return $this->successResult(array_merge([
            'message' => 'Unequipped class special: ' . $classSpecialEquipped->gameClassSpecial->name,
        ], $this->getSpecials($character)));
    }

    /**
     * give xp to a class rank for the characters current class.
     *
     * @throws Exception
     */
    public function giveXpToClassRank(Character $character, int $killCount = 1): void
    {
        if ($killCount <= 0) {
            return;
        }

        if ($killCount === 1) {
            $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

            if ($classRank->level >= ClassRankValue::MAX_LEVEL) {
                return;
            }

            $classRank->update([
                'current_xp' => $classRank->current_xp + ClassRankValue::XP_PER_KILL,
            ]);

            $classRank = $classRank->refresh();

            $this->battleMessageHandler->handleClassRankMessage($character->user, ClassRanksMessageTypes::XP_FOR_CLASS_RANKS, $character->class->name, ClassRankValue::XP_PER_KILL, $classRank->current_xp);

            if ($classRank->current_xp >= $classRank->required_xp) {
                $classRank->update([
                    'level' => $classRank->level + 1,
                    'current_xp' => 0,
                ]);

                event(new ServerMessageEvent($character->user, 'You gained a new class rank in: ' . $character->class->name));
            }

            return;
        }

        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        if (is_null($classRank) || $classRank->level >= ClassRankValue::MAX_LEVEL) {
            return;
        }

        $startingLevel = (int) $classRank->level;

        [$newLevel, $newCurrentXp, $levelsGained] = $this->applyKillCountToProgression(
            (int) $classRank->level,
            (int) $classRank->current_xp,
            (int) $classRank->required_xp,
            (int) ClassRankValue::XP_PER_KILL,
            $killCount,
            (int) ClassRankValue::MAX_LEVEL
        );

        $classRank->update([
            'level' => $newLevel,
            'current_xp' => $newCurrentXp,
        ]);

        $classRank = $classRank->refresh();

        $this->battleMessageHandler->handleClassRankMessage(
            $character->user,
            ClassRanksMessageTypes::XP_FOR_CLASS_RANKS,
            $character->class->name,
            (int) ClassRankValue::XP_PER_KILL * $killCount,
            $classRank->current_xp
        );

        if ($levelsGained > 0) {
            for ($gainedLevelIndex = 0; $gainedLevelIndex < $levelsGained; $gainedLevelIndex++) {
                $newMessageLevel = $startingLevel + $gainedLevelIndex + 1;

                if ($newMessageLevel > ClassRankValue::MAX_LEVEL) {
                    break;
                }

                event(new ServerMessageEvent($character->user, 'You gained a new class rank in: ' . $character->class->name));
            }
        }
    }

    /**
     * Give XP to equipped specials.
     *
     * @throws Exception
     */
    public function giveXpToEquippedClassSpecialties(Character $character, int $killCount = 1): void
    {
        if ($killCount <= 0) {
            return;
        }

        if ($killCount === 1) {
            $equippedSpecials = $character->classSpecialsEquipped()->where('equipped', true)->get();

            foreach ($equippedSpecials as $special) {
                if ($special->level >= ClassSpecialValue::MAX_LEVEL) {
                    continue;
                }

                $special->update([
                    'current_xp' => $special->current_xp + ClassSpecialValue::XP_PER_KILL,
                ]);

                $special = $special->refresh();

                $this->battleMessageHandler->handleClassRankMessage($character->user, ClassRanksMessageTypes::XP_FOR_EQUIPPED_CLASS_SPECIALS, $character->class->name, ClassSpecialValue::XP_PER_KILL, $special->current_xp, null, $special->gameClassSpecial->name);

                if ($special->current_xp >= $special->required_xp) {
                    $special->update([
                        'level' => $special->level + 1,
                        'current_xp' => 0,
                    ]);

                    event(new ServerMessageEvent($character->user, 'Your class special:  ' . $special->gameClassSpecial->name . ' has gained a new level is now level: ' . $special->level));

                    $this->updateCharacterAttackTypes->updateCache($character->refresh());
                }
            }

            return;
        }

        $equippedSpecials = $character->classSpecialsEquipped()->where('equipped', true)->get();

        $didLevel = false;

        foreach ($equippedSpecials as $special) {
            if ($special->level >= ClassSpecialValue::MAX_LEVEL) {
                continue;
            }

            $startingLevel = (int) $special->level;

            [$newLevel, $newCurrentXp, $levelsGained] = $this->applyKillCountToProgression(
                (int) $special->level,
                (int) $special->current_xp,
                (int) $special->required_xp,
                (int) ClassSpecialValue::XP_PER_KILL,
                $killCount,
                (int) ClassSpecialValue::MAX_LEVEL
            );

            $special->update([
                'level' => $newLevel,
                'current_xp' => $newCurrentXp,
            ]);

            $special = $special->refresh();

            $this->battleMessageHandler->handleClassRankMessage(
                $character->user,
                ClassRanksMessageTypes::XP_FOR_EQUIPPED_CLASS_SPECIALS,
                $character->class->name,
                (int) ClassSpecialValue::XP_PER_KILL * $killCount,
                $special->current_xp,
                null,
                $special->gameClassSpecial->name
            );

            if ($levelsGained > 0) {
                $didLevel = true;

                for ($gainedLevelIndex = 0; $gainedLevelIndex < $levelsGained; $gainedLevelIndex++) {
                    $newMessageLevel = $startingLevel + $gainedLevelIndex + 1;

                    if ($newMessageLevel > ClassSpecialValue::MAX_LEVEL) {
                        break;
                    }

                    event(new ServerMessageEvent(
                        $character->user,
                        'Your class special:  ' . $special->gameClassSpecial->name . ' has gained a new level is now level: ' . $newMessageLevel
                    ));
                }
            }
        }

        if ($didLevel) {
            $this->updateCharacterAttackTypes->updateCache($character->refresh());
        }
    }

    /**
     * Give XP to all applicable weapon masteries for the current class.
     *
     * @throws Exception
     */
    public function
    giveXpToMasteries(Character $character, int $killCount = 1): void
    {
        if ($killCount <= 0) {
            return;
        }

        if ($killCount === 1) {
            $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

            $inventory = $this->fetchEquipped($character);

            if (is_null($inventory)) {
                return;
            }

            foreach (ItemType::allWeaponTypes() as $type) {

                $inventorySlot = $inventory->where('item.type', $type)->first();

                if (! is_null($inventorySlot)) {
                    $weaponMastery = $classRank->weaponMasteries()->where('weapon_type', $type)->first();

                    if ($weaponMastery->level >= WeaponMasteryValue::MAX_LEVEL) {
                        continue;
                    }

                    $weaponMastery->update([
                        'current_xp' => $weaponMastery->current_xp + WeaponMasteryValue::XP_PER_KILL,
                    ]);

                    $weaponMastery = $weaponMastery->refresh();

                    $weaponMasteryName = ItemType::getProperNameForType($type);

                    $this->battleMessageHandler->handleClassRankMessage($character->user, ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES, $character->class->name, WeaponMasteryValue::XP_PER_KILL, $weaponMastery->current_xp, $weaponMasteryName);

                    if ($weaponMastery->current_xp >= $weaponMastery->required_xp) {
                        $weaponMastery->update([
                            'level' => $weaponMastery->level + 1,
                            'current_xp' => 0,
                        ]);

                        $weaponMastery = $weaponMastery->refresh();

                        $this->updateCharacterAttackTypes->updateCache($character->refresh());

                        event(new ServerMessageEvent(
                            $character->user,
                            'Your class: ' .
                            $classRank->gameClass->name . ' has gained a new level in (Weapon Masteries): ' .
                            $weaponMasteryName .
                            ' and is now level: ' . $weaponMastery->level
                        ));
                    }
                }
            }

            return;
        }

        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        $inventory = $this->fetchEquipped($character);

        if (is_null($inventory) || is_null($classRank)) {
            return;
        }

        $didLevel = false;

        foreach (ItemType::allWeaponTypes() as $type) {

            $inventorySlot = $inventory->where('item.type', $type)->first();

            if (! is_null($inventorySlot)) {
                $weaponMastery = $classRank->weaponMasteries()->where('weapon_type', $type)->first();

                if (is_null($weaponMastery) || $weaponMastery->level >= WeaponMasteryValue::MAX_LEVEL) {
                    continue;
                }

                $startingLevel = (int) $weaponMastery->level;

                [$newLevel, $newCurrentXp, $levelsGained] = $this->applyKillCountToProgression(
                    (int) $weaponMastery->level,
                    (int) $weaponMastery->current_xp,
                    (int) $weaponMastery->required_xp,
                    (int) WeaponMasteryValue::XP_PER_KILL,
                    $killCount,
                    (int) WeaponMasteryValue::MAX_LEVEL
                );

                $weaponMastery->update([
                    'level' => $newLevel,
                    'current_xp' => $newCurrentXp,
                ]);

                $weaponMastery = $weaponMastery->refresh();

                $weaponMasteryName = ItemType::getProperNameForType($type);

                $this->battleMessageHandler->handleClassRankMessage(
                    $character->user,
                    ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES,
                    $character->class->name,
                    (int) WeaponMasteryValue::XP_PER_KILL * $killCount,
                    $weaponMastery->current_xp,
                    $weaponMasteryName
                );

                if ($levelsGained > 0) {
                    $didLevel = true;

                    for ($gainedLevelIndex = 0; $gainedLevelIndex < $levelsGained; $gainedLevelIndex++) {
                        $newMessageLevel = $startingLevel + $gainedLevelIndex + 1;

                        if ($newMessageLevel > WeaponMasteryValue::MAX_LEVEL) {
                            break;
                        }

                        event(new ServerMessageEvent(
                            $character->user,
                            'Your class: ' .
                            $classRank->gameClass->name . ' has gained a new level in (Weapon Masteries): ' .
                            $weaponMasteryName .
                            ' and is now level: ' . $newMessageLevel
                        ));
                    }
                }
            }
        }

        if ($didLevel) {
            $this->updateCharacterAttackTypes->updateCache($character->refresh());
        }
    }

    protected function isClassLocked(Character $character, CharacterClassRank $classRank): bool
    {
        if (
            ! is_null($classRank->gameClass->primary_required_class_id) &&
            ! is_null($classRank->gameClass->secondary_required_class_id)
        ) {

            $primaryRequiredClassId = $classRank->gameClass->primary_required_class_id;
            $secondaryRequiredClassId = $classRank->gameClass->secondary_required_class_id;

            $primaryClassRank = $character->classRanks->where('game_class_id', $primaryRequiredClassId)->first();
            $secondaryClassRank = $character->classRanks->where('game_class_id', $secondaryRequiredClassId)->first();

            return ! (($primaryClassRank->level >= $classRank->gameClass->primary_required_class_level) &&
                ($secondaryClassRank->level >= $classRank->gameClass->secondary_required_class_level));
        }

        return false;
    }

    private function applyKillCountToProgression(
        int $currentLevel,
        int $currentXp,
        int $requiredXp,
        int $xpPerKill,
        int $killCount,
        int $maxLevel
    ): array {
        if ($killCount <= 0) {
            return [$currentLevel, $currentXp, 0];
        }

        if ($currentLevel >= $maxLevel) {
            return [$currentLevel, 0, 0];
        }

        if ($requiredXp <= 0 || $xpPerKill <= 0) {
            return [$currentLevel, $currentXp, 0];
        }

        $startingLevel = $currentLevel;

        $killsToFirstLevel = $this->killsToReachThreshold($currentXp, $requiredXp, $xpPerKill);

        if ($killCount < $killsToFirstLevel) {
            return [$currentLevel, $currentXp + ($xpPerKill * $killCount), 0];
        }

        $currentLevel++;

        if ($currentLevel >= $maxLevel) {
            return [$maxLevel, 0, $maxLevel - $startingLevel];
        }

        $remainingKills = $killCount - $killsToFirstLevel;

        $killsPerLevel = $this->killsToReachThreshold(0, $requiredXp, $xpPerKill);

        $levelsAvailable = $maxLevel - $currentLevel;

        $additionalLevels = intdiv($remainingKills, $killsPerLevel);

        if ($additionalLevels > $levelsAvailable) {
            $additionalLevels = $levelsAvailable;
        }

        $currentLevel += $additionalLevels;

        $remainingKills -= $additionalLevels * $killsPerLevel;

        if ($currentLevel >= $maxLevel) {
            return [$maxLevel, 0, $maxLevel - $startingLevel];
        }

        $newCurrentXp = $xpPerKill * $remainingKills;

        return [$currentLevel, $newCurrentXp, $currentLevel - $startingLevel];
    }

    private function killsToReachThreshold(int $currentXp, int $requiredXp, int $xpPerKill): int
    {
        if ($xpPerKill >= $requiredXp) {
            return 1;
        }

        if ($currentXp >= $requiredXp) {
            return 1;
        }

        $remaining = $requiredXp - $currentXp;

        return (int) ceil($remaining / $xpPerKill);
    }
}
