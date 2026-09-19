<?php

namespace App\Game\ClassRanks\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameClassSpecial;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\ClassRanks\Transformers\ClassDetailTransformer;
use App\Game\ClassRanks\Transformers\ClassMasteryDetailTransformer;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\ClassRanksMessageTypes;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Throwable;

class ClassRankService
{
    use FetchEquipped, ResponseBuilder;

    private ?Throwable $classRankXpCalculationFailure = null;

    /**
     * @param BattleMessageHandler $battleMessageHandler
     * @param CharacterAreaGemEffects $characterAreaGemEffects
     * @param ClassDetailTransformer $classDetailTransformer
     * @param ClassMasteryDetailTransformer $classMasteryDetailTransformer
     */
    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly CharacterAreaGemEffects $characterAreaGemEffects,
        private readonly ClassDetailTransformer $classDetailTransformer,
        private readonly ClassMasteryDetailTransformer $classMasteryDetailTransformer,
    ) {}

    /**
     * Return the invalid whole-XP-per-kill calculation failure recorded during the most recent
     * Class Rank/Specialty XP operation, if one occurred, so the caller can fail the owning
     * reward operation instead of treating a corrupted calculation as a silent or extreme XP
     * reward.
     *
     * @return ?Throwable
     */
    public function classRankXpCalculationFailure(): ?Throwable
    {
        return $this->classRankXpCalculationFailure;
    }

    /**
     * Resolve the Gem-adjusted Class Rank XP awarded per kill for the character, or null when the
     * calculation is invalid.
     *
     * @param Character $character
     * @param ?float $preResolvedGemBonus
     * @return ?int
     */
    private function resolveClassRankXpPerKill(Character $character, ?float $preResolvedGemBonus = null): ?int
    {
        $bonus = $preResolvedGemBonus ?? $this->characterAreaGemEffects->resolveForCharacterId($character->id)->rewardEffect(AreaGemRewardEffect::CHARACTER_CLASS_RANK_XP_BONUS);

        return $this->wholeXpPerKill(ClassRankValue::XP_PER_KILL * (1 + $bonus));
    }

    /**
     * Resolve the Gem-adjusted Class Specialty XP awarded per kill for the character, or null when
     * the calculation is invalid.
     *
     * @param Character $character
     * @param ?float $preResolvedGemBonus
     * @return ?int
     */
    private function resolveClassSpecialtyXpPerKill(Character $character, ?float $preResolvedGemBonus = null): ?int
    {
        $bonus = $preResolvedGemBonus ?? $this->characterAreaGemEffects->resolveForCharacterId($character->id)->rewardEffect(AreaGemRewardEffect::CHARACTER_CLASS_SPECIALTY_XP_GAIN);

        return $this->wholeXpPerKill(ClassSpecialValue::XP_PER_KILL * (1 + $bonus));
    }

    /**
     * Validate and extract the genuine integer value of an already-whole Gem-adjusted XP-per-kill
     * amount, or null when the calculation is invalid. This XP-per-kill domain never exceeds the
     * platform integer range, so a failed validation means the calculation that produced this
     * amount is corrupted; that failure is recorded on `classRankXpCalculationFailure()` instead of
     * silently applying a zero, `PHP_INT_MAX`, or `PHP_INT_MIN` Class Rank/Specialty XP reward, so
     * the caller can fail the owning reward operation.
     *
     * @param float $xpPerKill
     * @return ?int
     */
    private function wholeXpPerKill(float $xpPerKill): ?int
    {
        $wholeXpPerKill = filter_var(round($xpPerKill), FILTER_VALIDATE_INT);

        if ($wholeXpPerKill === false) {
            $this->classRankXpCalculationFailure = new RuntimeException(
                'Invalid whole Class Rank/Specialty XP-per-kill amount calculated: '.$xpPerKill.' cannot be represented as an integer.',
            );

            return null;
        }

        return $wholeXpPerKill;
    }

    /**
     * Get the class specials for the character.
     *
     * @param Character $character
     * @return array
     */
    public function getSpecials(Character $character): array
    {
        $classSpecialsEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial.gameClass')->where('equipped', '=', true)->get();
        $classSpecialsNotEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial.gameClass')->where('equipped', '=', false)->get();

        $damageStatAmount = $character->getInformation()->statMod($character->damage_stat);

        return [
            'class_specialties' => GameClassSpecial::with('gameClass')->get()->transform(function ($special) {
                $special->class_name = $special->gameClass->name;
                $special->class_mastery = $this->classMasteryDetailTransformer->transform($special);

                return $special;
            }),
            'specials_equipped' => array_values($classSpecialsEquipped->transform(function ($specialEquipped) use ($damageStatAmount) {
                $specialEquipped->class_name = $specialEquipped->gameClassSpecial->gameClass->name;
                $specialEquipped->specialty_damage = $specialEquipped->gameClassSpecial->specialty_damage
                    + $specialEquipped->gameClassSpecial->increase_specialty_damage_per_level * $specialEquipped->level
                    + $damageStatAmount * $specialEquipped->gameClassSpecial->specialty_damage_uses_damage_stat_amount;
                $specialEquipped->class_mastery = $this->classMasteryDetailTransformer->transform($specialEquipped->gameClassSpecial);
                $specialEquipped->is_mastered = $specialEquipped->level >= ClassSpecialValue::MAX_LEVEL;

                return $specialEquipped;
            })->toArray()),
            'class_ranks' => $character->classRanks->toArray(),
            'other_class_specials' => array_values($classSpecialsNotEquipped->transform(function ($special) use ($damageStatAmount) {
                $special->class_name = $special->gameClassSpecial->gameClass->name;
                $special->specialty_damage = $special->gameClassSpecial->specialty_damage
                    + $special->gameClassSpecial->increase_specialty_damage_per_level * $special->level
                    + $damageStatAmount * $special->gameClassSpecial->specialty_damage_uses_damage_stat_amount;
                $special->class_mastery = $this->classMasteryDetailTransformer->transform($special->gameClassSpecial);
                $special->is_mastered = $special->level >= ClassSpecialValue::MAX_LEVEL;

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
    public function getClassRanks(Character $character): array
    {
        $classRanks = $character->classRanks()->with([
            'gameClass',
            'gameClass.primaryClassRequired',
            'gameClass.secondaryClassRequired',
            'weaponMasteries',
        ])->get();

        $characterClassRanks = $classRanks;

        $classRanks = $classRanks->transform(function ($classRank) use ($character, $characterClassRanks) {
            $classRank->class_name = $classRank->gameClass->name;
            $classRank->is_active = $classRank->gameClass->id === $character->game_class_id;
            $classRank->is_locked = $this->isClassLocked($character, $classRank);
            $classRank->is_mastered = $classRank->level >= ClassRankValue::MAX_LEVEL;

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
                    'is_mastered' => $mastery->level >= WeaponMasteryValue::MAX_LEVEL,
                ];

                $sortedWeaponMasteries[] = $masteryData;
            }

            $classRank->weapon_masteries = $sortedWeaponMasteries;

            $primaryClass = $classRank->gameClass->primaryClassRequired;
            $secondaryClass = $classRank->gameClass->secondaryClassRequired;

            $classRank->primary_class_name = ! is_null($primaryClass) ? $primaryClass->name : null;
            $classRank->secondary_class_name = ! is_null($secondaryClass) ? $secondaryClass->name : null;
            $classRank->primary_class_required_level = $classRank->gameClass->primary_required_class_level;
            $classRank->secondary_class_required_level = $classRank->gameClass->secondary_required_class_level;

            $classRank->class_detail = $this->classDetailTransformer->transform($classRank->gameClass);
            $classRank->unlock_progress = $this->resolveUnlockProgress($classRank->gameClass, $characterClassRanks);

            return $classRank;
        })->sortByDesc(fn ($item) => $item->is_active)
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
     * Resolve the display-only prerequisite unlock progress for a Class, or null when it has no prerequisite pair.
     *
     * @param GameClass $gameClass
     * @param Collection $characterClassRanks
     * @return ?array
     */
    private function resolveUnlockProgress(GameClass $gameClass, Collection $characterClassRanks): ?array
    {
        if (is_null($gameClass->primary_required_class_id) || is_null($gameClass->secondary_required_class_id)) {
            return null;
        }

        $primaryClassRank = $characterClassRanks->where('game_class_id', $gameClass->primary_required_class_id)->first();
        $secondaryClassRank = $characterClassRanks->where('game_class_id', $gameClass->secondary_required_class_id)->first();

        $primaryCurrentLevel = ! is_null($primaryClassRank) ? $primaryClassRank->level : 0;
        $secondaryCurrentLevel = ! is_null($secondaryClassRank) ? $secondaryClassRank->level : 0;

        return [
            'primary' => [
                'id' => $gameClass->primaryClassRequired->id,
                'name' => $gameClass->primaryClassRequired->name,
                'current_level' => $primaryCurrentLevel,
                'required_level' => $gameClass->primary_required_class_level,
                'is_met' => $primaryCurrentLevel >= $gameClass->primary_required_class_level,
            ],
            'secondary' => [
                'id' => $gameClass->secondaryClassRequired->id,
                'name' => $gameClass->secondaryClassRequired->name,
                'current_level' => $secondaryCurrentLevel,
                'required_level' => $gameClass->secondary_required_class_level,
                'is_met' => $secondaryCurrentLevel >= $gameClass->secondary_required_class_level,
            ],
        ];
    }

    /**
     * Equip a class specialty
     *
     * @param Character $character
     * @param GameClassSpecial $gameClassSpecial
     * @return array
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

        if (is_null($classRank) || $classRank->level < $gameClassSpecial->requires_class_rank_level) {
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

        CharacterAttackTypesCacheBuilder::dispatch($character);

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $this->successResult(array_merge([
            'message' => 'Equipped class special: '.$gameClassSpecial->name,
        ], $this->getSpecials($character)));
    }

    /**
     * Unequip the specialty.
     *
     * @param Character $character
     * @param CharacterClassSpecialtiesEquipped $classSpecialEquipped
     * @return array
     */
    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped)
    {
        $specialEquipped = $character->classSpecialsEquipped()->where('id', $classSpecialEquipped->id)->first();

        if (is_null($specialEquipped)) {
            return $this->errorResult('You do not own that.');
        }

        $specialEquipped->update(['equipped' => false]);

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $this->successResult(array_merge([
            'message' => 'Unequipped class special: '.$classSpecialEquipped->gameClassSpecial->name,
        ], $this->getSpecials($character)));
    }

    /**
     * Swap a currently equipped Class Specialty for an accessible target one.
     *
     * @param Character $character
     * @param GameClassSpecial $target
     * @param CharacterClassSpecialtiesEquipped $replacement
     * @return array
     */
    public function swapSpecialty(Character $character, GameClassSpecial $target, CharacterClassSpecialtiesEquipped $replacement): array
    {
        if ($replacement->character_id !== $character->id) {
            return $this->errorResult('You do not own that.');
        }

        if (! $replacement->equipped) {
            return $this->errorResult('You do not own that.');
        }

        $alreadyEquipped = $character->classSpecialsEquipped->where('game_class_special_id', $target->id)->where('equipped', true)->first();

        if (! is_null($alreadyEquipped)) {
            return $this->errorResult('That Class Specialty is already equipped.');
        }

        $classRank = $character->classRanks->where('game_class_id', $target->game_class_id)->first();

        if (is_null($classRank) || $classRank->level < $target->requires_class_rank_level) {
            return $this->errorResult('You do not have the required class rank level for this.');
        }

        if ($target->specialty_damage > 0) {
            $otherEquippedDamageSpecialties = $character->classSpecialsEquipped
                ->where('id', '!=', $replacement->id)
                ->where('equipped', true)
                ->where('gameClassSpecial.specialty_damage', '>', 0)
                ->count();

            if ($otherEquippedDamageSpecialties > 0) {
                return $this->errorResult('You already have a damage specialty equipped and cannot equip another one.');
            }
        }

        $replacement->update(['equipped' => false]);

        $existingProgress = $character->classSpecialsEquipped()
            ->where('game_class_special_id', $target->id)
            ->where('equipped', false)
            ->first();

        if (! is_null($existingProgress)) {
            $existingProgress->update(['equipped' => true]);
        } else {
            $character->classSpecialsEquipped()->create([
                'character_id' => $character->id,
                'game_class_special_id' => $target->id,
                'level' => 1,
                'current_xp' => 0,
                'required_xp' => ClassSpecialValue::XP_PER_LEVEL,
                'equipped' => true,
            ]);
        }

        $character = $character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $this->successResult(array_merge([
            'message' => 'Equipped class special: '.$target->name,
        ], $this->getSpecials($character)));
    }

    /**
     * give xp to a class rank for the characters current class.
     *
     * @param Character $character
     * @param int $killCount
     * @param ?float $preResolvedGemBonus
     * @return void
     */
    public function giveXpToClassRank(Character $character, int $killCount = 1, ?float $preResolvedGemBonus = null): void
    {
        $this->classRankXpCalculationFailure = null;

        if ($killCount <= 0) {
            return;
        }

        $xpPerKill = $this->resolveClassRankXpPerKill($character, $preResolvedGemBonus);

        if (is_null($xpPerKill)) {
            return;
        }

        if ($killCount === 1) {
            $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

            if ($classRank->level >= ClassRankValue::MAX_LEVEL) {
                $classRank->update([
                    'level' => ClassRankValue::MAX_LEVEL,
                    'current_xp' => 0,
                ]);

                return;
            }

            $classRank->update([
                'current_xp' => $classRank->current_xp + $xpPerKill,
            ]);

            $classRank = $classRank->refresh();

            $this->battleMessageHandler->handleClassRankMessage($character->user, ClassRanksMessageTypes::XP_FOR_CLASS_RANKS, $character->class->name, $xpPerKill, $classRank->current_xp);

            if ($classRank->current_xp >= $classRank->required_xp) {
                $classRank->update([
                    'level' => min($classRank->level + 1, ClassRankValue::MAX_LEVEL),
                    'current_xp' => 0,
                ]);

                event(new ServerMessageEvent($character->user, 'You gained a new class rank in: '.$character->class->name));
            }

            return;
        }

        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        if (is_null($classRank)) {
            return;
        }

        if ($classRank->level >= ClassRankValue::MAX_LEVEL) {
            $classRank->update([
                'level' => ClassRankValue::MAX_LEVEL,
                'current_xp' => 0,
            ]);

            return;
        }

        $startingLevel = $classRank->level;

        [$newLevel, $newCurrentXp, $levelsGained] = $this->applyKillCountToProgression(
            $classRank->level,
            $classRank->current_xp,
            $classRank->required_xp,
            $xpPerKill,
            $killCount,
            ClassRankValue::MAX_LEVEL
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
            $xpPerKill * $killCount,
            $classRank->current_xp
        );

        if ($levelsGained > 0) {
            for ($gainedLevelIndex = 0; $gainedLevelIndex < $levelsGained; $gainedLevelIndex++) {
                $newMessageLevel = $startingLevel + $gainedLevelIndex + 1;

                if ($newMessageLevel > ClassRankValue::MAX_LEVEL) {
                    break;
                }

                event(new ServerMessageEvent($character->user, 'You gained a new class rank in: '.$character->class->name));
            }
        }
    }

    /**
     * Give XP to equipped specials.
     *
     * @param Character $character
     * @param int $killCount
     * @param ?float $preResolvedGemBonus
     * @return void
     */
    public function giveXpToEquippedClassSpecialties(Character $character, int $killCount = 1, ?float $preResolvedGemBonus = null): void
    {
        if ($killCount <= 0) {
            return;
        }

        $xpPerKill = $this->resolveClassSpecialtyXpPerKill($character, $preResolvedGemBonus);

        if (is_null($xpPerKill)) {
            return;
        }

        if ($killCount === 1) {
            $equippedSpecials = $character->classSpecialsEquipped()->where('equipped', true)->get();

            $didLevel = false;

            foreach ($equippedSpecials as $special) {
                if ($special->level >= ClassSpecialValue::MAX_LEVEL) {
                    $special->update([
                        'level' => ClassSpecialValue::MAX_LEVEL,
                        'current_xp' => 0,
                    ]);

                    continue;
                }

                $special->update([
                    'current_xp' => $special->current_xp + $xpPerKill,
                ]);

                $special = $special->refresh();

                $this->battleMessageHandler->handleClassRankMessage($character->user, ClassRanksMessageTypes::XP_FOR_EQUIPPED_CLASS_SPECIALS, $character->class->name, $xpPerKill, $special->current_xp, null, $special->gameClassSpecial->name);

                if ($special->current_xp >= $special->required_xp) {
                    $special->update([
                        'level' => min($special->level + 1, ClassSpecialValue::MAX_LEVEL),
                        'current_xp' => 0,
                    ]);

                    $special = $special->refresh();

                    event(new ServerMessageEvent($character->user, 'Your class special:  '.$special->gameClassSpecial->name.' has gained a new level is now level: '.$special->level));

                    $didLevel = true;
                }
            }

            if ($didLevel) {
                CharacterAttackTypesCacheBuilder::dispatch($character->refresh());
            }

            return;
        }

        $equippedSpecials = $character->classSpecialsEquipped()->where('equipped', true)->get();

        $didLevel = false;

        foreach ($equippedSpecials as $special) {
            if ($special->level >= ClassSpecialValue::MAX_LEVEL) {
                $special->update([
                    'level' => ClassSpecialValue::MAX_LEVEL,
                    'current_xp' => 0,
                ]);

                continue;
            }

            $startingLevel = $special->level;

            [$newLevel, $newCurrentXp, $levelsGained] = $this->applyKillCountToProgression(
                $special->level,
                $special->current_xp,
                $special->required_xp,
                $xpPerKill,
                $killCount,
                ClassSpecialValue::MAX_LEVEL
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
                $xpPerKill * $killCount,
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
                        'Your class special:  '.$special->gameClassSpecial->name.' has gained a new level is now level: '.$newMessageLevel
                    ));
                }
            }
        }

        if ($didLevel) {
            CharacterAttackTypesCacheBuilder::dispatch($character->refresh());
        }
    }

    /**
     * Give XP to all applicable weapon masteries for the current class.
     *
     * @param Character $character
     * @param int $killCount
     * @return void
     */
    public function giveXpToMasteries(Character $character, int $killCount = 1): void
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

            $didLevel = false;

            foreach (ItemType::weaponMasteryTypes() as $type) {

                $inventorySlot = $inventory->where('item.type', $type)->first();

                if (! is_null($inventorySlot)) {
                    $weaponMastery = $classRank->weaponMasteries()->where('weapon_type', $type)->first();

                    if (is_null($weaponMastery)) {
                        continue;
                    }

                    if ($weaponMastery->level >= WeaponMasteryValue::MAX_LEVEL) {
                        $weaponMastery->update([
                            'level' => WeaponMasteryValue::MAX_LEVEL,
                            'current_xp' => 0,
                        ]);

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
                            'level' => min($weaponMastery->level + 1, WeaponMasteryValue::MAX_LEVEL),
                            'current_xp' => 0,
                        ]);

                        $weaponMastery = $weaponMastery->refresh();

                        $didLevel = true;

                        event(new ServerMessageEvent(
                            $character->user,
                            'Your class: '.
                                $classRank->gameClass->name.' has gained a new level in (Weapon Masteries): '.
                                $weaponMasteryName.
                                ' and is now level: '.$weaponMastery->level
                        ));
                    }
                }
            }

            if ($didLevel) {
                CharacterAttackTypesCacheBuilder::dispatch($character->refresh());
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

                if (is_null($weaponMastery)) {
                    continue;
                }

                if ($weaponMastery->level >= WeaponMasteryValue::MAX_LEVEL) {
                    $weaponMastery->update([
                        'level' => WeaponMasteryValue::MAX_LEVEL,
                        'current_xp' => 0,
                    ]);

                    continue;
                }

                $startingLevel = $weaponMastery->level;

                [$newLevel, $newCurrentXp, $levelsGained] = $this->applyKillCountToProgression(
                    $weaponMastery->level,
                    $weaponMastery->current_xp,
                    $weaponMastery->required_xp,
                    WeaponMasteryValue::XP_PER_KILL,
                    $killCount,
                    WeaponMasteryValue::MAX_LEVEL
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
                    WeaponMasteryValue::XP_PER_KILL * $killCount,
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
                            'Your class: '.
                                $classRank->gameClass->name.' has gained a new level in (Weapon Masteries): '.
                                $weaponMasteryName.
                                ' and is now level: '.$newMessageLevel
                        ));
                    }
                }
            }
        }

        if ($didLevel) {
            CharacterAttackTypesCacheBuilder::dispatch($character->refresh());
        }
    }

    /**
     * Determine whether the Class Rank is locked behind its prerequisite Class levels.
     *
     * @param Character $character
     * @param CharacterClassRank $classRank
     * @return bool
     */
    private function isClassLocked(Character $character, CharacterClassRank $classRank): bool
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

    /**
     * Apply a batch of kills to a level/xp progression and return the resulting level, xp, and levels gained.
     *
     * @param int $currentLevel
     * @param int $currentXp
     * @param int $requiredXp
     * @param int $xpPerKill
     * @param int $killCount
     * @param int $maxLevel
     * @return array
     */
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
            return [$maxLevel, 0, 0];
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

    /**
     * Calculate the number of kills required to reach the next XP threshold.
     *
     * @param int $currentXp
     * @param int $requiredXp
     * @param int $xpPerKill
     * @return int
     */
    private function killsToReachThreshold(int $currentXp, int $requiredXp, int $xpPerKill): int
    {
        if ($xpPerKill >= $requiredXp) {
            return 1;
        }

        if ($currentXp >= $requiredXp) {
            return 1;
        }

        $remaining = $requiredXp - $currentXp;

        return intdiv($remaining + $xpPerKill - 1, $xpPerKill);
    }
}
