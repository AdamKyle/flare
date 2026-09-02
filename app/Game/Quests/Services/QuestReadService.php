<?php

namespace App\Game\Quests\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Quest;
use App\Game\Quests\Transformers\QuestBrowseOptionsTransformer;
use App\Game\Quests\Transformers\QuestDetailTransformer;
use App\Game\Quests\Transformers\QuestTreeNodeTransformer;
use App\Game\Quests\Values\QuestKind;
use App\Game\Raids\Services\RaidMapConflictService;
use Illuminate\Support\Collection;

class QuestReadService
{
    /**
     * @param  QuestTreeNodeTransformer  $questTreeNodeTransformer  Factual Quest tree node transformer.
     * @param  QuestDetailTransformer  $questDetailTransformer  Factual Quest detail transformer.
     * @param  QuestBrowseOptionsTransformer  $questBrowseOptionsTransformer  Factual Quest browse-options transformer.
     * @param  RaidMapConflictService  $raidMapConflictService  Authoritative Raid-to-Game-Map ownership service.
     */
    public function __construct(
        private readonly QuestTreeNodeTransformer $questTreeNodeTransformer,
        private readonly QuestDetailTransformer $questDetailTransformer,
        private readonly QuestBrowseOptionsTransformer $questBrowseOptionsTransformer,
        private readonly RaidMapConflictService $raidMapConflictService,
    ) {}

    /**
     * Build the factual, optionally Map- and Kind-filtered Quest tree.
     *
     * The Map and Kind filters narrow which root Quests are visible; a visible root Quest's
     * full descendant chain is always rendered underneath it regardless of each descendant's
     * own resolved kind, since a chain's members belong to the same navigable story.
     *
     * @param  int|null  $gameMapId  Game Map id to filter root Quests by, when given.
     * @param  QuestKind|null  $kind  Quest kind to filter root Quests by, when given.
     * @return array<int, array<string, mixed>> Root-ordered factual Quest tree nodes.
     */
    public function tree(?int $gameMapId, ?QuestKind $kind): array
    {
        $quests = Quest::query()->with(['npc.gameMap', 'raid'])->get()->keyBy('id');

        $childIdsByParent = $this->groupChildIdsByParent($quests);
        $kindByQuestId = $quests->mapWithKeys(
            fn (Quest $quest) => [$quest->id => QuestKind::resolve($quest, $childIdsByParent->has($quest->id))]
        );
        $raidIdsOnMap = $this->resolveRaidIdsOnMap($gameMapId, $kind);

        $roots = $quests->filter(fn (Quest $quest) => $this->isRoot($quest, $quests))
            ->filter(fn (Quest $quest) => is_null($kind) || $kindByQuestId->get($quest->id) === $kind)
            ->filter(fn (Quest $quest) => $this->rootMatchesGameMapFilter($quest, $gameMapId, $kind, $quests, $childIdsByParent, $raidIdsOnMap));

        return $this->sortQuests($roots)
            ->map(fn (Quest $quest) => $this->buildNode($quest, $quests, $childIdsByParent, $kindByQuestId, []))
            ->values()
            ->all();
    }

    /**
     * Return the factual Quest browse options: the ordered Game Maps a Quest browser may select
     * from, and the currently configured default Game Map, when one exists.
     *
     * @return array{default_game_map_id: int|null, game_maps: array<int, array{id: int, name: string}>} Quest browse options.
     */
    public function browseOptions(): array
    {
        $gameMaps = GameMap::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'default']);

        return $this->questBrowseOptionsTransformer->transform($gameMaps);
    }

    /**
     * Build the full factual detail representation for a single Quest.
     *
     * @param  Quest  $quest  Quest to load and transform.
     * @return array<string, mixed> Full factual Quest detail representation.
     */
    public function detail(Quest $quest): array
    {
        $quest->loadRelations();
        $quest->load(['childQuests', 'factionLoyaltyNpc.gameMap']);

        $hasChildQuests = $quest->childQuests->isNotEmpty();

        return $this->questDetailTransformer->transform(
            $quest,
            QuestKind::resolve($quest, $hasChildQuests),
            $this->resolveRequiredQuestChain($quest),
            $this->resolveUnlockedSkill($quest),
        );
    }

    /**
     * Resolve the required Quest chain identities in stored order.
     *
     * @param  Quest  $quest  Quest to resolve the required chain for.
     * @return array<int, array{id: int, name: string}> Ordered required Quest chain identities.
     */
    private function resolveRequiredQuestChain(Quest $quest): array
    {
        $requiredIds = $quest->required_quest_chain ?? [];

        if (empty($requiredIds)) {
            return [];
        }

        $quests = Quest::whereIn('id', $requiredIds)->get(['id', 'name'])->keyBy('id');

        return collect($requiredIds)
            ->map(fn (int $id) => $quests->has($id) ? ['id' => $id, 'name' => $quests->get($id)->name] : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve the unlocked Game Skill identity, when the Quest unlocks one.
     *
     * @param  Quest  $quest  Quest to resolve the unlocked skill for.
     * @return array{id: int, name: string, type: int}|null Unlocked skill identity.
     */
    private function resolveUnlockedSkill(Quest $quest): ?array
    {
        if (! $quest->unlocks_skill || is_null($quest->unlocks_skill_type)) {
            return null;
        }

        $skill = GameSkill::where('type', $quest->unlocks_skill_type)->first();

        if (is_null($skill)) {
            return null;
        }

        return [
            'id' => $skill->id,
            'name' => $skill->name,
            'type' => $quest->unlocks_skill_type,
        ];
    }

    /**
     * Recursively build a Quest tree node and its filtered-set children, guarding against
     * malformed parent/child cycles in legacy data.
     *
     * @param  Quest  $quest  Quest to build a node for.
     * @param  Collection<int, Quest>  $quests  Every loaded Quest, keyed by id.
     * @param  Collection<int, array<int, int>>  $childIdsByParent  Child Quest ids grouped by `parent_quest_id`.
     * @param  Collection<int, QuestKind>  $kindByQuestId  Resolved Quest kind, keyed by Quest id.
     * @param  array<int, int>  $visitedIds  Quest ids already visited on this recursion path.
     * @return array<string, mixed> Factual Quest tree node.
     */
    private function buildNode(Quest $quest, Collection $quests, Collection $childIdsByParent, Collection $kindByQuestId, array $visitedIds): array
    {
        if (in_array($quest->id, $visitedIds, true)) {
            return $this->questTreeNodeTransformer->transform($quest, $kindByQuestId->get($quest->id), []);
        }

        $visitedIds[] = $quest->id;

        $childQuests = $this->sortQuests(
            collect($childIdsByParent->get($quest->id, []))
                ->map(fn (int $id) => $quests->get($id))
                ->filter()
        );

        $childNodes = $childQuests
            ->map(fn (Quest $child) => $this->buildNode($child, $quests, $childIdsByParent, $kindByQuestId, $visitedIds))
            ->values()
            ->all();

        return $this->questTreeNodeTransformer->transform($quest, $kindByQuestId->get($quest->id), $childNodes);
    }

    /**
     * Resolve the Raid ids that occupy the requested Game Map, once per request, only when the
     * request is actually Raid Map-filtered.
     *
     * @param  int|null  $gameMapId  Game Map id to filter root Quests by, when given.
     * @param  QuestKind|null  $kind  Quest kind to filter root Quests by, when given.
     * @return array<int, int> Raid ids that occupy the requested Game Map.
     */
    private function resolveRaidIdsOnMap(?int $gameMapId, ?QuestKind $kind): array
    {
        if (is_null($gameMapId) || $kind !== QuestKind::RAID) {
            return [];
        }

        return $this->raidMapConflictService->raidIdsOnMaps([$gameMapId]);
    }

    /**
     * Determine whether a candidate root Quest belongs to the requested Game Map, using
     * Map-membership semantics that depend on the requested Quest kind.
     *
     * Chain roots (and the nullable-kind generic tree) match when any Quest in the subtree has
     * a Quest-giver NPC on the target Map. One Off roots match only through their own
     * Quest-giver NPC. Raid roots match through the Raid's own factual Map ownership, resolved
     * by `RaidMapConflictService`, regardless of any Quest-giver NPC placement.
     *
     * @param  Quest  $quest  Candidate root Quest.
     * @param  int|null  $gameMapId  Game Map id to filter root Quests by, when given.
     * @param  QuestKind|null  $kind  Quest kind to filter root Quests by, when given.
     * @param  Collection<int, Quest>  $quests  Every loaded Quest, keyed by id.
     * @param  Collection<int, array<int, int>>  $childIdsByParent  Child Quest ids grouped by `parent_quest_id`.
     * @param  array<int, int>  $raidIdsOnMap  Raid ids that occupy the requested Game Map.
     * @return bool Whether the root Quest belongs to the requested Game Map.
     */
    private function rootMatchesGameMapFilter(
        Quest $quest,
        ?int $gameMapId,
        ?QuestKind $kind,
        Collection $quests,
        Collection $childIdsByParent,
        array $raidIdsOnMap,
    ): bool {
        if (is_null($gameMapId)) {
            return true;
        }

        if ($kind === QuestKind::ONE_OFF) {
            return $this->questBelongsToGameMap($quest, $gameMapId);
        }

        if ($kind === QuestKind::RAID) {
            return ! is_null($quest->raid_id) && in_array($quest->raid_id, $raidIdsOnMap, true);
        }

        return $this->subtreeContainsGameMap($quest, $quests, $childIdsByParent, $gameMapId, []);
    }

    /**
     * Determine whether the given Quest's own Quest-giver NPC belongs to the target Game Map.
     *
     * Uses the already-loaded `npc.gameMap` relation identity rather than the raw
     * `npc.game_map_id` attribute, so the comparison reflects the actual related record.
     *
     * @param  Quest  $quest  Quest to check.
     * @param  int  $gameMapId  Target Game Map id.
     * @return bool Whether the Quest's Quest-giver NPC belongs to the target Game Map.
     */
    private function questBelongsToGameMap(Quest $quest, int $gameMapId): bool
    {
        if (is_null($quest->npc) || is_null($quest->npc->gameMap)) {
            return false;
        }

        return $quest->npc->gameMap->id === $gameMapId;
    }

    /**
     * Determine whether the given Quest or any Quest in its descendant subtree belongs to the
     * target Game Map, using only the already-loaded Quest/NPC/GameMap data.
     *
     * Guards against malformed parent/child cycles in legacy data by tracking visited ids and
     * stops recursing as soon as a match is found.
     *
     * @param  Quest  $quest  Quest to check, along with its descendants.
     * @param  Collection<int, Quest>  $quests  Every loaded Quest, keyed by id.
     * @param  Collection<int, array<int, int>>  $childIdsByParent  Child Quest ids grouped by `parent_quest_id`.
     * @param  int  $gameMapId  Target Game Map id.
     * @param  array<int, int>  $visitedIds  Quest ids already visited on this recursion path.
     * @return bool Whether the Quest subtree contains a member belonging to the target Game Map.
     */
    private function subtreeContainsGameMap(Quest $quest, Collection $quests, Collection $childIdsByParent, int $gameMapId, array $visitedIds): bool
    {
        if (in_array($quest->id, $visitedIds, true)) {
            return false;
        }

        if ($this->questBelongsToGameMap($quest, $gameMapId)) {
            return true;
        }

        $visitedIds[] = $quest->id;

        foreach ($childIdsByParent->get($quest->id, []) as $childId) {
            $child = $quests->get($childId);

            if (! is_null($child) && $this->subtreeContainsGameMap($child, $quests, $childIdsByParent, $gameMapId, $visitedIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a Quest is a visible tree root: it has no parent, or its parent no
     * longer exists in the currently loaded Quest set.
     *
     * @param  Quest  $quest  Quest to check.
     * @param  Collection<int, Quest>  $quests  Every loaded Quest, keyed by id.
     * @return bool Whether the Quest is a tree root.
     */
    private function isRoot(Quest $quest, Collection $quests): bool
    {
        return is_null($quest->parent_quest_id) || ! $quests->has($quest->parent_quest_id);
    }

    /**
     * Group every Quest's id under its `parent_quest_id`.
     *
     * @param  Collection<int, Quest>  $quests  Every loaded Quest, keyed by id.
     * @return Collection<int, array<int, int>> Child Quest ids grouped by parent Quest id.
     */
    private function groupChildIdsByParent(Collection $quests): Collection
    {
        return $quests
            ->filter(fn (Quest $quest) => ! is_null($quest->parent_quest_id))
            ->groupBy('parent_quest_id')
            ->map(fn (Collection $children) => $children->pluck('id')->all());
    }

    /**
     * Sort Quests deterministically: name ascending, id ascending tie-break.
     *
     * @param  Collection<int, Quest>  $quests  Quests to sort.
     * @return Collection<int, Quest> Deterministically sorted Quests.
     */
    private function sortQuests(Collection $quests): Collection
    {
        return $quests->sort(fn (Quest $a, Quest $b) => [$a->name, $a->id] <=> [$b->name, $b->id]);
    }
}
