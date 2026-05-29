<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\ItemSkillProgression;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Values\FactionLevel;
use App\Game\Core\Values\FactionType;
use Illuminate\Console\Command;

class FixInvalidProgressionLevels extends Command
{
    protected $signature = 'fix:invalid-progression-levels';

    protected $description = 'Fixes invalid progression levels and XP values.';

    public function handle(): void
    {
        $this->info('Fixing invalid progression levels...');

        $skillsUpdated = $this->normalizeSkills();
        $charactersUpdated = $this->normalizeCharacters();
        $classRanksUpdated = $this->normalizeClassRanks();
        $classSpecialsUpdated = $this->normalizeClassSpecials();
        $weaponMasteriesUpdated = $this->normalizeWeaponMasteries();
        $itemSkillsUpdated = $this->normalizeItemSkills();
        $passiveSkillsUpdated = $this->normalizePassiveSkills();
        $factionsUpdated = $this->normalizeFactions();
        $factionLoyaltyNpcsUpdated = $this->normalizeFactionLoyaltyNpcs();

        $this->info('Skills updated: '.$skillsUpdated);
        $this->info('Characters updated: '.$charactersUpdated);
        $this->info('Class ranks updated: '.$classRanksUpdated);
        $this->info('Class specials updated: '.$classSpecialsUpdated);
        $this->info('Weapon masteries updated: '.$weaponMasteriesUpdated);
        $this->info('Item skills updated: '.$itemSkillsUpdated);
        $this->info('Passive skills updated: '.$passiveSkillsUpdated);
        $this->info('Factions updated: '.$factionsUpdated);
        $this->info('Faction loyalty NPCs updated: '.$factionLoyaltyNpcsUpdated);
        $this->info('Invalid progression cleanup complete.');
    }

    protected function normalizeSkills(): int
    {
        $updatedCount = 0;

        Skill::with('baseSkill')->chunkById(100, function ($skills) use (&$updatedCount): void {
            foreach ($skills as $skill) {
                if (is_null($skill->baseSkill)) {
                    continue;
                }

                $maxLevel = $skill->baseSkill->max_level;

                if ($skill->level < $maxLevel) {
                    continue;
                }

                if (
                    $skill->level === $maxLevel &&
                    $skill->xp === 0 &&
                    $skill->currently_training === false &&
                    $skill->xp_towards === 0.0
                ) {
                    continue;
                }

                $skill->update([
                    'level' => $maxLevel,
                    'xp' => 0,
                    'currently_training' => false,
                    'xp_towards' => 0.0,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizeCharacters(): int
    {
        $updatedCount = 0;
        $configuredMaxLevel = $this->getConfiguredMaxLevel();
        $characterService = resolve(CharacterService::class);

        Character::with('inventory.slots.item')->chunkById(100, function ($characters) use (&$updatedCount, $configuredMaxLevel, $characterService): void {
            foreach ($characters as $character) {
                $maxLevel = $this->hasContinueLevelingItem($character)
                    ? $configuredMaxLevel
                    : MaxLevel::MAX_LEVEL;

                if ($this->normalizeCharacter($character, $maxLevel, $characterService)) {
                    $updatedCount++;
                }
            }
        });

        return $updatedCount;
    }

    protected function normalizeCharacter(Character $character, int $maxLevel, CharacterService $characterService): bool
    {
        $originalLevel = $character->level;
        $originalXp = $character->xp;
        $originalXpNext = $character->xp_next;

        if ($character->level >= $maxLevel) {
            if ($character->level !== $maxLevel || $character->xp !== 0) {
                $character->update([
                    'level' => $maxLevel,
                    'xp' => 0,
                ]);
            }

            $character = $character->refresh();

            return $character->level !== $originalLevel ||
                $character->xp !== $originalXp ||
                $character->xp_next !== $originalXpNext;
        }

        if ($character->xp_next <= 0) {
            if ($character->xp !== 0) {
                $character->update([
                    'xp' => 0,
                ]);
            }

            $character = $character->refresh();

            return $character->level !== $originalLevel ||
                $character->xp !== $originalXp ||
                $character->xp_next !== $originalXpNext;
        }

        while ($character->level < $maxLevel && $character->xp >= $character->xp_next) {
            $leftOverXp = $character->xp - $character->xp_next;

            $characterService->levelUpCharacter($character, $leftOverXp);

            $character = $character->refresh();

            if ($character->level >= $maxLevel) {
                if ($character->level !== $maxLevel || $character->xp !== 0) {
                    $character->update([
                        'level' => $maxLevel,
                        'xp' => 0,
                    ]);

                    $character = $character->refresh();
                }

                break;
            }

            if ($character->xp_next <= 0) {
                $character->update([
                    'xp' => 0,
                ]);

                $character = $character->refresh();

                break;
            }
        }

        return $character->level !== $originalLevel ||
            $character->xp !== $originalXp ||
            $character->xp_next !== $originalXpNext;
    }

    protected function normalizeClassRanks(): int
    {
        $updatedCount = 0;

        CharacterClassRank::query()->chunkById(100, function ($classRanks) use (&$updatedCount): void {
            foreach ($classRanks as $classRank) {
                $level = min($classRank->level, ClassRankValue::MAX_LEVEL);
                $currentXp = $classRank->current_xp;
                $requiredXp = $classRank->required_xp;

                if (
                    $level >= ClassRankValue::MAX_LEVEL ||
                    $currentXp >= ClassRankValue::XP_PER_LEVEL ||
                    $currentXp >= $requiredXp
                ) {
                    $currentXp = 0;
                }

                if (
                    $classRank->level === $level &&
                    $classRank->current_xp === $currentXp &&
                    $classRank->required_xp === ClassRankValue::XP_PER_LEVEL
                ) {
                    continue;
                }

                $classRank->update([
                    'level' => $level,
                    'current_xp' => $currentXp,
                    'required_xp' => ClassRankValue::XP_PER_LEVEL,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizeClassSpecials(): int
    {
        $updatedCount = 0;

        CharacterClassSpecialtiesEquipped::query()->chunkById(100, function ($classSpecials) use (&$updatedCount): void {
            foreach ($classSpecials as $classSpecial) {
                $level = min($classSpecial->level, ClassSpecialValue::MAX_LEVEL);
                $currentXp = $classSpecial->current_xp;
                $requiredXp = $classSpecial->required_xp;

                if (
                    $level >= ClassSpecialValue::MAX_LEVEL ||
                    $currentXp >= ClassSpecialValue::XP_PER_LEVEL ||
                    $currentXp >= $requiredXp
                ) {
                    $currentXp = 0;
                }

                if (
                    $classSpecial->level === $level &&
                    $classSpecial->current_xp === $currentXp &&
                    $classSpecial->required_xp === ClassSpecialValue::XP_PER_LEVEL
                ) {
                    continue;
                }

                $classSpecial->update([
                    'level' => $level,
                    'current_xp' => $currentXp,
                    'required_xp' => ClassSpecialValue::XP_PER_LEVEL,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizeWeaponMasteries(): int
    {
        $updatedCount = 0;

        CharacterClassRankWeaponMastery::query()->chunkById(100, function ($weaponMasteries) use (&$updatedCount): void {
            foreach ($weaponMasteries as $weaponMastery) {
                $level = min($weaponMastery->level, WeaponMasteryValue::MAX_LEVEL);
                $currentXp = $weaponMastery->current_xp;
                $requiredXp = $weaponMastery->required_xp;

                if (
                    $level >= WeaponMasteryValue::MAX_LEVEL ||
                    $currentXp >= WeaponMasteryValue::XP_PER_LEVEL ||
                    $currentXp >= $requiredXp
                ) {
                    $currentXp = 0;
                }

                if (
                    $weaponMastery->level === $level &&
                    $weaponMastery->current_xp === $currentXp &&
                    $weaponMastery->required_xp === WeaponMasteryValue::XP_PER_LEVEL
                ) {
                    continue;
                }

                $weaponMastery->update([
                    'level' => $level,
                    'current_xp' => $currentXp,
                    'required_xp' => WeaponMasteryValue::XP_PER_LEVEL,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizeItemSkills(): int
    {
        $updatedCount = 0;

        ItemSkillProgression::with('itemSkill')->chunkById(100, function ($itemSkillProgressions) use (&$updatedCount): void {
            foreach ($itemSkillProgressions as $itemSkillProgression) {
                if (is_null($itemSkillProgression->itemSkill)) {
                    continue;
                }

                $maxLevel = $itemSkillProgression->itemSkill->max_level;
                $totalKillsNeeded = $itemSkillProgression->itemSkill->total_kills_needed;
                $currentLevel = min($itemSkillProgression->current_level, $maxLevel);
                $currentKill = max($itemSkillProgression->current_kill, 0);
                $isTraining = $itemSkillProgression->is_training;

                if ($currentLevel >= $maxLevel) {
                    $currentKill = 0;
                    $isTraining = false;
                } elseif ($totalKillsNeeded <= 0) {
                    $currentKill = 0;
                } elseif ($currentKill >= $totalKillsNeeded) {
                    [$currentLevel, $currentKill, $isTraining] = $this->applyStoredKillsToItemSkill(
                        $currentLevel,
                        $currentKill,
                        $totalKillsNeeded,
                        $maxLevel,
                        $isTraining
                    );
                }

                if (
                    $itemSkillProgression->current_level === $currentLevel &&
                    $itemSkillProgression->current_kill === $currentKill &&
                    $itemSkillProgression->is_training === $isTraining
                ) {
                    continue;
                }

                $itemSkillProgression->update([
                    'current_level' => $currentLevel,
                    'current_kill' => $currentKill,
                    'is_training' => $isTraining,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizePassiveSkills(): int
    {
        $updatedCount = 0;

        CharacterPassiveSkill::with('passiveSkill')->chunkById(100, function ($passiveSkills) use (&$updatedCount): void {
            foreach ($passiveSkills as $passiveSkill) {
                if (is_null($passiveSkill->passiveSkill)) {
                    continue;
                }

                $maxLevel = $passiveSkill->passiveSkill->max_level;
                $currentLevel = min(max($passiveSkill->current_level, 0), $maxLevel);
                $hoursToNext = $passiveSkill->hours_to_next;
                $startedAt = $passiveSkill->started_at;
                $completedAt = $passiveSkill->completed_at;

                if ($currentLevel >= $maxLevel) {
                    $hoursToNext = 0;
                    $startedAt = null;
                    $completedAt = null;
                }

                if (
                    $passiveSkill->current_level === $currentLevel &&
                    $passiveSkill->hours_to_next === $hoursToNext &&
                    $passiveSkill->started_at === $startedAt &&
                    $passiveSkill->completed_at === $completedAt
                ) {
                    continue;
                }

                $passiveSkill->update([
                    'current_level' => $currentLevel,
                    'hours_to_next' => $hoursToNext,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizeFactions(): int
    {
        $updatedCount = 0;

        Faction::query()->chunkById(100, function ($factions) use (&$updatedCount): void {
            foreach ($factions as $faction) {
                $currentLevel = min(max($faction->current_level, 0), FactionLevel::MAX_LEVEL);
                $pointsNeeded = FactionLevel::getPointsNeeded($currentLevel);
                $currentPoints = max($faction->current_points, 0);
                $maxed = false;

                if ($currentLevel >= FactionLevel::MAX_LEVEL) {
                    $currentPoints = 0;
                    $maxed = true;
                } elseif ($currentPoints > $pointsNeeded) {
                    $currentPoints = $pointsNeeded;
                }

                $title = FactionType::getTitle($currentLevel);

                if (
                    $faction->current_level === $currentLevel &&
                    $faction->current_points === $currentPoints &&
                    $faction->points_needed === $pointsNeeded &&
                    $faction->maxed === $maxed &&
                    $faction->title === $title
                ) {
                    continue;
                }

                $faction->update([
                    'current_level' => $currentLevel,
                    'current_points' => $currentPoints,
                    'points_needed' => $pointsNeeded,
                    'maxed' => $maxed,
                    'title' => $title,
                ]);

                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    protected function normalizeFactionLoyaltyNpcs(): int
    {
        $updatedCount = 0;

        FactionLoyaltyNpc::with('factionLoyaltyNpcTasks')->chunkById(100, function ($factionLoyaltyNpcs) use (&$updatedCount): void {
            foreach ($factionLoyaltyNpcs as $factionLoyaltyNpc) {
                if ($factionLoyaltyNpc->current_level < $factionLoyaltyNpc->max_level) {
                    continue;
                }

                $wasUpdated = false;

                if ($factionLoyaltyNpc->current_level !== $factionLoyaltyNpc->max_level) {
                    $factionLoyaltyNpc->update([
                        'current_level' => $factionLoyaltyNpc->max_level,
                    ]);

                    $wasUpdated = true;
                }

                if (
                    ! is_null($factionLoyaltyNpc->factionLoyaltyNpcTasks) &&
                    ! empty($factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
                ) {
                    $factionLoyaltyNpc->factionLoyaltyNpcTasks->update([
                        'fame_tasks' => [],
                    ]);

                    $wasUpdated = true;
                }

                if ($wasUpdated) {
                    $updatedCount++;
                }
            }
        });

        return $updatedCount;
    }

    protected function applyStoredKillsToItemSkill(
        int $currentLevel,
        int $currentKill,
        int $totalKillsNeeded,
        int $maxLevel,
        bool $isTraining
    ): array {
        $levelsAvailable = $maxLevel - $currentLevel;
        $levelsFromStoredKills = intdiv($currentKill, $totalKillsNeeded);

        if ($levelsFromStoredKills >= $levelsAvailable) {
            return [$maxLevel, 0, false];
        }

        $currentLevel += $levelsFromStoredKills;
        $currentKill %= $totalKillsNeeded;

        if ($currentLevel >= $maxLevel) {
            return [$maxLevel, 0, false];
        }

        return [$currentLevel, $currentKill, $isTraining];
    }

    protected function hasContinueLevelingItem(Character $character): bool
    {
        if (is_null($character->inventory)) {
            return false;
        }

        return $character->inventory->slots->contains(function ($slot): bool {
            return ! is_null($slot->item) && $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        });
    }

    protected function getConfiguredMaxLevel(): int
    {
        $configuration = MaxLevelConfiguration::query()->first();

        if (is_null($configuration)) {
            return MaxLevel::MAX_LEVEL;
        }

        return $configuration->max_level;
    }
}