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
     * Canonical Quest Game Map browse order, name ascending within any unlisted remainder.
     */
    private const QUEST_GAME_MAP_ORDER = [
        'Surface',
        'Labyrinth',
        'Dungeons',
        'Shadow Plane',
        'Purgatory',
        'Ice Plane',
        'Delusional Memories',
        'Twisted Memories',
    ];

    public function __construct(
        private readonly QuestTreeNodeTransformer $questTreeNodeTransformer,
        private readonly QuestDetailTransformer $questDetailTransformer,
        private readonly QuestBrowseOptionsTransformer $questBrowseOptionsTransformer,
        private readonly RaidMapConflictService $raidMapConflictService,
    ) {}

    /**
     * Build the factual, optionally Map- and Kind-filtered Quest tree.
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
            ->filter(fn (Quest $quest) => $this->rootMatchesGameMapFilter($quest, $gameMapId, $kind, $raidIdsOnMap));

        $visibleRoots = $this->limitToCanonicalChainRoot($this->sortQuests($roots), $kind, $gameMapId);

        return $visibleRoots
            ->map(fn (Quest $quest) => $this->buildNode($quest, $quests, $childIdsByParent, $kindByQuestId, []))
            ->values()
            ->all();
    }

    /**
     * Return selectable Quest Game Maps and the configured default Game Map.
     */
    public function browseOptions(): array
    {
        $gameMaps = GameMap::query()
            ->whereNull('generated_parent_game_map_id')
            ->whereNull('generated_map_type')
            ->get(['id', 'name', 'default']);

        return $this->questBrowseOptionsTransformer->transform($this->sortGameMapsByCanonicalOrder($gameMaps));
    }

    /**
     * Build the full factual detail representation for a single Quest.
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
     * Resolve the required Quest chain dependency identities in stored order.
     */
    private function resolveRequiredQuestChain(Quest $quest): array
    {
        $requiredIds = $quest->required_quest_chain ?? [];

        if (empty($requiredIds)) {
            return [];
        }

        $quests = Quest::whereIn('id', $requiredIds)
            ->get(['id', 'name', 'parent_quest_id', 'required_quest_id', 'required_quest_chain'])
            ->keyBy('id');

        return collect($requiredIds)
            ->map(fn (int $id) => $quests->has($id) ? $this->requiredQuestChainEntry($quests->get($id)) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Build one Required Quest Chain entry's factual dependency identity.
     */
    private function requiredQuestChainEntry(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'parent_quest_id' => $quest->parent_quest_id,
            'required_quest_id' => $quest->required_quest_id,
            'required_quest_chain_ids' => $quest->required_quest_chain ?? [],
        ];
    }

    /**
     * Resolve the unlocked Game Skill identity, when the Quest unlocks one.
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
     * Recursively build a Quest tree node while guarding against malformed cycles.
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
     * Resolve Raid ids occupying the requested Game Map for Raid-filtered reads.
     */
    private function resolveRaidIdsOnMap(?int $gameMapId, ?QuestKind $kind): array
    {
        if (is_null($gameMapId) || $kind !== QuestKind::RAID) {
            return [];
        }

        return $this->raidMapConflictService->raidIdsOnMaps([$gameMapId]);
    }

    /**
     * Determine whether a Quest tree root belongs to the requested Game Map.
     */
    private function rootMatchesGameMapFilter(
        Quest $quest,
        ?int $gameMapId,
        ?QuestKind $kind,
        array $raidIdsOnMap,
    ): bool {
        if (is_null($gameMapId)) {
            return true;
        }

        if ($kind === QuestKind::RAID) {
            return ! is_null($quest->raid_id) && in_array($quest->raid_id, $raidIdsOnMap, true);
        }

        return $this->questBelongsToGameMap($quest, $gameMapId);
    }

    /**
     * Determine whether the given Quest's own Quest-giver NPC belongs to the target Game Map.
     */
    private function questBelongsToGameMap(Quest $quest, int $gameMapId): bool
    {
        if (is_null($quest->npc) || is_null($quest->npc->gameMap)) {
            return false;
        }

        return $quest->npc->gameMap->id === $gameMapId;
    }

    /**
     * Determine whether a Quest is a visible root in the loaded Quest set.
     */
    private function isRoot(Quest $quest, Collection $quests): bool
    {
        return is_null($quest->parent_quest_id) || ! $quests->has($quest->parent_quest_id);
    }

    /**
     * Group every Quest's id under its `parent_quest_id`.
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
     */
    private function sortQuests(Collection $quests): Collection
    {
        return $quests->sort(fn (Quest $a, Quest $b) => [$a->name, $a->id] <=> [$b->name, $b->id]);
    }

    /**
     * Sort Game Maps by the canonical Quest browse order, unlisted Maps after by name then id.
     */
    private function sortGameMapsByCanonicalOrder(Collection $gameMaps): Collection
    {
        return $gameMaps->sort(function (GameMap $a, GameMap $b) {
            $aIndex = array_search($a->name, self::QUEST_GAME_MAP_ORDER, true);
            $bIndex = array_search($b->name, self::QUEST_GAME_MAP_ORDER, true);

            $aRank = $aIndex === false ? PHP_INT_MAX : $aIndex;
            $bRank = $bIndex === false ? PHP_INT_MAX : $bIndex;

            return [$aRank, $a->name, $a->id] <=> [$bRank, $b->name, $b->id];
        })->values();
    }

    /**
     * Limit a selected-Map Chain browse to its canonical root Quest.
     */
    private function limitToCanonicalChainRoot(Collection $sortedRoots, ?QuestKind $kind, ?int $gameMapId): Collection
    {
        if ($kind !== QuestKind::CHAIN || is_null($gameMapId)) {
            return $sortedRoots;
        }

        return $sortedRoots->take(1);
    }
}
