<?php

namespace App\Game\Quests\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Quest;
use App\Game\Quests\Transformers\QuestDetailTransformer;
use App\Game\Quests\Transformers\QuestTreeNodeTransformer;
use App\Game\Quests\Values\QuestKind;
use Illuminate\Support\Collection;

class QuestReadService
{
    /**
     * @param  QuestTreeNodeTransformer  $questTreeNodeTransformer  Factual Quest tree node transformer.
     * @param  QuestDetailTransformer  $questDetailTransformer  Factual Quest detail transformer.
     */
    public function __construct(
        private readonly QuestTreeNodeTransformer $questTreeNodeTransformer,
        private readonly QuestDetailTransformer $questDetailTransformer,
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

        $roots = $quests->filter(fn (Quest $quest) => $this->isRoot($quest, $quests))
            ->filter(fn (Quest $quest) => is_null($gameMapId) || optional($quest->npc)->game_map_id === $gameMapId)
            ->filter(fn (Quest $quest) => is_null($kind) || $kindByQuestId->get($quest->id) === $kind);

        return $this->sortQuests($roots)
            ->map(fn (Quest $quest) => $this->buildNode($quest, $quests, $childIdsByParent, $kindByQuestId, []))
            ->values()
            ->all();
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
