<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Collection;

class PassiveSkillAssigner
{
    /**
     * Assign passive skills to the character in topological order using bulk inserts.
     *
     * @param CharacterBuildState $state
     * @param Closure $next
     * @return CharacterBuildState
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $now = $state->getNow() ?? now();

        $allPassives = $this->loadAllPassives();

        if ($allPassives->isEmpty()) {
            return $next($state);
        }

        $questGates = $this->mapQuestGatesForPassives($allPassives);
        $completedQuestIds = $character->questsCompleted->pluck('quest_id')->values();

        $topLevel = $allPassives->whereNull('parent_skill_id')->values();
        $children = $allPassives->whereNotNull('parent_skill_id')->values();

        $topRows = $this->buildTopLevelRows(
            $topLevel,
            $character,
            $now,
            $questGates,
            $completedQuestIds
        );

        if (!empty($topRows)) {
            CharacterPassiveSkill::query()->insert($topRows);
        }

        $insertedMap = $this->loadInsertedMapFor($character, $topLevel);

        $this->processChildren(
            $children,
            $character,
            $now,
            $questGates,
            $completedQuestIds,
            $insertedMap
        );

        return $next($state);
    }

    /**
     * Load all passive skills required for initial seeding.
     *
     * @return Collection
     */
    private function loadAllPassives(): Collection
    {
        return PassiveSkill::select([
            'id',
            'parent_skill_id',
            'hours_per_level',
            'is_locked',
            'unlocks_at_level',
        ])->get();
    }

    /**
     * Build insert rows for top-level passive skills.
     *
     * @param Collection $topLevel
     * @param Character $character
     * @param DateTimeInterface $timestamp
     * @param Collection $questGates
     * @param Collection $completedQuestIds
     * @return array
     */
    private function buildTopLevelRows(
        Collection $topLevel,
        Character $character,
        DateTimeInterface $timestamp,
        Collection $questGates,
        Collection $completedQuestIds
    ): array {
        return $topLevel->map(function (PassiveSkill $passive) use ($character, $timestamp, $questGates, $completedQuestIds) {
            $hasQuestGate = $questGates->has($passive->id);
            $questCompleted = $hasQuestGate ? $completedQuestIds->contains($questGates->get($passive->id)) : false;

            $isLocked = $this->determineLockedStateForNewCharacter(
                $passive,
                false,
                $passive->unlocks_at_level,
                $hasQuestGate,
                $questCompleted
            );

            return [
                'character_id' => $character->id,
                'passive_skill_id' => $passive->id,
                'current_level' => 0,
                'hours_to_next' => $passive->hours_per_level,
                'is_locked' => $isLocked,
                'parent_skill_id' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();
    }

    /**
     * Load the map of inserted CharacterPassiveSkill ids keyed by passive_skill_id.
     *
     * @param Character $character
     * @param Collection $topLevel
     * @return Collection
     */
    private function loadInsertedMapFor(Character $character, Collection $topLevel): Collection
    {
        return CharacterPassiveSkill::where('character_id', $character->id)
            ->whereIn('passive_skill_id', $topLevel->pluck('id'))
            ->pluck('id', 'passive_skill_id');
    }

    /**
     * Process child passive skills in waves once their parents exist.
     *
     * @param Collection $children
     * @param Character $character
     * @param DateTimeInterface $timestamp
     * @param Collection $questGates
     * @param Collection $completedQuestIds
     * @param Collection $insertedMap
     * @return void
     */
    private function processChildren(
        Collection $children,
        Character $character,
        DateTimeInterface $timestamp,
        Collection $questGates,
        Collection $completedQuestIds,
        Collection $insertedMap
    ): void {
        $remaining = $children->keyBy('id');

        while ($remaining->isNotEmpty()) {
            $ready = $remaining->filter(function (PassiveSkill $passive) use ($insertedMap) {
                if (is_null($passive->parent_skill_id)) {
                    return false;
                }

                return $insertedMap->has($passive->parent_skill_id);
            });

            if ($ready->isEmpty()) {
                break;
            }

            $readyRows = $ready->map(function (PassiveSkill $passive) use ($character, $timestamp, $questGates, $completedQuestIds, $insertedMap) {
                $hasQuestGate = $questGates->has($passive->id);
                $questCompleted = $hasQuestGate ? $completedQuestIds->contains($questGates->get($passive->id)) : false;

                $isLocked = $this->determineLockedStateForNewCharacter(
                    $passive,
                    true,
                    $passive->unlocks_at_level,
                    $hasQuestGate,
                    $questCompleted
                );

                $parentCharacterPassiveId = $insertedMap->get($passive->parent_skill_id);

                return [
                    'character_id' => $character->id,
                    'passive_skill_id' => $passive->id,
                    'current_level' => 0,
                    'hours_to_next' => $passive->hours_per_level,
                    'is_locked' => $isLocked,
                    'parent_skill_id' => $parentCharacterPassiveId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->values()->all();

            CharacterPassiveSkill::query()->insert($readyRows);

            $newMap = CharacterPassiveSkill::where('character_id', $character->id)
                ->whereIn('passive_skill_id', $ready->keys())
                ->pluck('id', 'passive_skill_id');

            $insertedMap = $insertedMap->merge($newMap);

            $remaining = $remaining->except($ready->keys()->all());
        }
    }

    /**
     * Build a map of quest gates keyed by the passive skill id.
     *
     * @param Collection $passives
     * @return Collection
     */
    private function mapQuestGatesForPassives(Collection $passives): Collection
    {
        $passiveIds = $passives->pluck('id');

        $quests = Quest::whereIn('unlocks_passive_id', $passiveIds)->get(['id', 'unlocks_passive_id']);

        return $quests->keyBy('unlocks_passive_id')->map(function ($quest) {
            return $quest->id;
        });
    }

    /**
     * Determine the lock state for a new character before any leveling or quest completion.
     *
     * @param PassiveSkill $passiveSkill
     * @param bool $hasParent
     * @param int|null $unlocksAtLevel
     * @param bool $hasQuestGate
     * @param bool $questCompleted
     * @return bool
     */
    private function determineLockedStateForNewCharacter(
        PassiveSkill $passiveSkill,
        bool $hasParent,
        ?int $unlocksAtLevel,
        bool $hasQuestGate,
        bool $questCompleted
    ): bool {
        $unlockLevel = $unlocksAtLevel ?? 0;

        if ($hasParent) {
            $isLocked = $unlockLevel > 0;
        } else {
            $isLocked = $passiveSkill->is_locked;
        }

        if ($hasQuestGate) {
            $isLocked = ! $questCompleted;
        }

        return $isLocked;
    }
}
