<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\GameClass;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Closure;
use DateTimeInterface;

class ClassRankAssigner
{
    /**
     * Create a class rank for every class and seed weapon masteries for each rank.
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $now = $state->getNow() ?? now();

        $gameClasses = GameClass::all();

        $gameClasses->each(function (GameClass $gameClass) use ($character, $now) {
            $classRank = $character->classRanks()->create([
                'character_id' => $character->id,
                'game_class_id' => $gameClass->id,
                'current_xp' => 0,
                'required_xp' => ClassRankValue::XP_PER_LEVEL,
                'level' => 0,
            ]);

            $rows = $this->buildWeaponMasteryRows($classRank, $now);

            if (! empty($rows)) {
                CharacterClassRankWeaponMastery::query()->insert($rows);
            }
        });

        return $next($state);
    }

    /**
     * Build weapon mastery insert rows for a given class rank.
     */
    private function buildWeaponMasteryRows(CharacterClassRank $classRank, DateTimeInterface $timestamp): array
    {
        return collect(ItemType::allWeaponTypes())->map(function (string $type) use ($classRank, $timestamp) {
            return [
                'character_class_rank_id' => $classRank->id,
                'weapon_type' => $type,
                'current_xp' => 0,
                'required_xp' => WeaponMasteryValue::XP_PER_LEVEL,
                'level' => $this->getDefaultLevel($classRank, $type),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();
    }

    /**
     * Get default level for weapon mastery based on class and mapping.
     */
    private function getDefaultLevel(CharacterClassRank $classRank, string $type): int
    {
        $mapping = ItemTypeMapping::getForClass($classRank->gameClass->name);

        if ($mapping === null) {
            return 0;
        }

        if (is_string($mapping)) {
            if ($type === $mapping) {
                return 5;
            } else {
                return 0;
            }
        }

        $position = array_search($type, $mapping, true);

        if ($position === false) {
            return 0;
        }

        $classType = $classRank->gameClass->type();

        if ($classType->isPrisoner()) {
            if ($position === 0) {
                return 5;
            } else {
                return 0;
            }
        }

        if ($classType->isMerchant()) {
            if ($position === 0) {
                return 2;
            } else {
                return 3;
            }
        }

        return 5;
    }
}
