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
    private Collection $passiveIdToCharacterPassiveId;
    private Collection $passiveIdToCurrentLevel;

    private Collection $pendingInsertRows;
    private Collection $pendingInsertPassiveIds;
    private Collection $pendingInsertPassiveIdSet;

    private Collection $pendingUpdateRows;

    public function __construct()
    {
        $this->passiveIdToCharacterPassiveId = collect();
        $this->passiveIdToCurrentLevel = collect();

        $this->resetBuffers();
    }

    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $passives = $this->loadPassives();

        if ($passives->isEmpty()) {
            return $next($state);
        }

        $this->resetBuffers();

        $now = $state->getNow() ?? now();

        $passiveIdToQuestId = $this->loadQuestGates($passives);
        $completedQuestIds = $character->questsCompleted->pluck('quest_id')->values();

        $existingPassives = $this->loadCharacterPassives($character);

        $this->initializeExistingMaps($existingPassives);

        $this->assignPassives(
            $character,
            $passives,
            $existingPassives,
            $passiveIdToQuestId,
            $completedQuestIds,
            $now
        );

        $this->flushUpdates();
        $this->flushInserts($character);

        dump('Calling Next from PassiveSkillAssigner');

        return $next($state);
    }

    private function resetBuffers(): void
    {
        $this->pendingInsertRows = collect();
        $this->pendingInsertPassiveIds = collect();
        $this->pendingInsertPassiveIdSet = collect();
        $this->pendingUpdateRows = collect();
    }

    private function initializeExistingMaps(Collection $existingPassives): void
    {
        $this->passiveIdToCharacterPassiveId = $existingPassives->pluck('id', 'passive_skill_id');
        $this->passiveIdToCurrentLevel = $existingPassives->pluck('current_level', 'passive_skill_id');
    }

    private function assignPassives(
        Character $character,
        Collection $passives,
        Collection $existingPassives,
        Collection $passiveIdToQuestId,
        Collection $completedQuestIds,
        DateTimeInterface $now
    ): void {
        foreach ($passives as $passiveSkill) {
            $existing = $existingPassives->get($passiveSkill->id);

            if ($existing !== null) {
                $this->queueUpdate($existing, $passiveSkill, $passiveIdToQuestId, $completedQuestIds, $now);
                $this->flushUpdatesIfAtLimit();

                continue;
            }

            if ($this->shouldFlushForParent($passiveSkill)) {
                $this->flushInserts($character);
            }

            $this->queueInsert($character, $passiveSkill, $passiveIdToQuestId, $completedQuestIds, $now);
            $this->flushInsertsIfAtLimit($character);
        }
    }

    private function queueUpdate(
        CharacterPassiveSkill $existing,
        PassiveSkill $passiveSkill,
        Collection $passiveIdToQuestId,
        Collection $completedQuestIds,
        DateTimeInterface $now
    ): void {
        $this->pendingUpdateRows->push([
            'id' => $existing->id,
            'is_locked' => $this->determineIsLocked($passiveSkill, $passiveIdToQuestId, $completedQuestIds),
            'updated_at' => $now,
        ]);
    }

    private function queueInsert(
        Character $character,
        PassiveSkill $passiveSkill,
        Collection $passiveIdToQuestId,
        Collection $completedQuestIds,
        DateTimeInterface $now
    ): void {
        $this->pendingInsertRows->push([
            'character_id' => $character->id,
            'passive_skill_id' => $passiveSkill->id,
            'current_level' => 0,
            'hours_to_next' => $passiveSkill->hours_per_level,
            'is_locked' => $this->determineIsLocked($passiveSkill, $passiveIdToQuestId, $completedQuestIds),
            'parent_skill_id' => $this->getParentCharacterPassiveId($passiveSkill),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->pendingInsertPassiveIds->push($passiveSkill->id);
        $this->pendingInsertPassiveIdSet->put($passiveSkill->id, true);
    }

    private function flushUpdatesIfAtLimit(): void
    {
        if ($this->pendingUpdateRows->count() < 100) {
            return;
        }

        $this->flushUpdates();
    }

    private function flushInsertsIfAtLimit(Character $character): void
    {
        if ($this->pendingInsertRows->count() < 100) {
            return;
        }

        $this->flushInserts($character);
    }

    private function shouldFlushForParent(PassiveSkill $passiveSkill): bool
    {
        $parentPassiveId = $passiveSkill->parent_skill_id;

        if ($parentPassiveId === null) {
            return false;
        }

        if ($this->passiveIdToCharacterPassiveId->has($parentPassiveId)) {
            return false;
        }

        if (!$this->pendingInsertPassiveIdSet->has($parentPassiveId)) {
            return false;
        }

        return true;
    }

    private function flushUpdates(): void
    {
        if ($this->pendingUpdateRows->isEmpty()) {
            return;
        }

        CharacterPassiveSkill::query()->upsert(
            $this->pendingUpdateRows->values()->all(),
            ['id'],
            ['is_locked', 'updated_at']
        );

        $this->pendingUpdateRows = collect();
    }

    private function flushInserts(Character $character): void
    {
        if ($this->pendingInsertRows->isEmpty()) {
            return;
        }

        CharacterPassiveSkill::query()->insert($this->pendingInsertRows->values()->all());

        $this->mergeInsertedMaps($character);

        $this->pendingInsertRows = collect();
        $this->pendingInsertPassiveIds = collect();
        $this->pendingInsertPassiveIdSet = collect();
    }

    private function mergeInsertedMaps(Character $character): void
    {
        if ($this->pendingInsertPassiveIds->isEmpty()) {
            return;
        }

        $newIds = CharacterPassiveSkill::query()
            ->where('character_id', $character->id)
            ->whereIn('passive_skill_id', $this->pendingInsertPassiveIds->values()->all())
            ->pluck('id', 'passive_skill_id');

        $this->passiveIdToCharacterPassiveId = $this->passiveIdToCharacterPassiveId->merge($newIds);

        $newLevels = $this->pendingInsertPassiveIds->mapWithKeys(function ($passiveId) {
            return [$passiveId => 0];
        });

        $this->passiveIdToCurrentLevel = $this->passiveIdToCurrentLevel->merge($newLevels);
    }

    private function determineIsLocked(
        PassiveSkill $passiveSkill,
        Collection $passiveIdToQuestId,
        Collection $completedQuestIds
    ): bool {
        $isLocked = $passiveSkill->is_locked;

        $parentPassiveId = $passiveSkill->parent_skill_id;

        if ($parentPassiveId !== null && $this->passiveIdToCurrentLevel->has($parentPassiveId)) {
            $isLocked = $passiveSkill->unlocks_at_level > $this->passiveIdToCurrentLevel->get($parentPassiveId);
        }

        $questId = $passiveIdToQuestId->get($passiveSkill->id);

        if ($questId === null) {
            return $isLocked;
        }

        return !$completedQuestIds->contains($questId);
    }

    private function getParentCharacterPassiveId(PassiveSkill $passiveSkill): ?int
    {
        $parentPassiveId = $passiveSkill->parent_skill_id;

        if ($parentPassiveId === null) {
            return null;
        }

        if (!$this->passiveIdToCharacterPassiveId->has($parentPassiveId)) {
            return null;
        }

        return $this->passiveIdToCharacterPassiveId->get($parentPassiveId);
    }

    private function loadPassives(): Collection
    {
        return PassiveSkill::query()
            ->select([
                'id',
                'parent_skill_id',
                'hours_per_level',
                'is_locked',
                'unlocks_at_level',
            ])
            ->get();
    }

    private function loadCharacterPassives(Character $character): Collection
    {
        return CharacterPassiveSkill::query()
            ->where('character_id', $character->id)
            ->get([
                'id',
                'passive_skill_id',
                'current_level',
                'is_locked',
            ])
            ->keyBy('passive_skill_id');
    }

    private function loadQuestGates(Collection $passives): Collection
    {
        $passiveIds = $passives->pluck('id')->values();

        if ($passiveIds->isEmpty()) {
            return collect();
        }

        return Quest::query()
            ->whereIn('unlocks_passive_id', $passiveIds)
            ->pluck('id', 'unlocks_passive_id');
    }
}
