<?php

namespace App\Admin\Quests\Support;

use App\Flare\Models\Quest;

class QuestImportGraphValidator
{
    /**
     * @var array<int, Quest>
     */
    private array $questCache = [];

    public function __construct(
        private readonly array $nameToId,
        private readonly array $rowsById,
    ) {}

    /**
     * Validate the complete parent, required-Quest, and required-Quest-chain graphs formed by the
     * workbook rows, combined with existing database edges, for cycles.
     */
    public function validate(): ?string
    {
        foreach ($this->rowsById as $id => $row) {
            $error = $this->parentError($id, $row['parent_quest_id'])
                ?? $this->requiredQuestError($id, $row['required_quest_id'])
                ?? $this->requiredChainError($id, $row['required_quest_chain']);

            if (! is_null($error)) {
                return $error;
            }
        }

        return null;
    }

    /**
     * Resolve the parent-Quest-selection error for one workbook row, when its selection is invalid.
     */
    private function parentError(int $id, ?int $parentId): ?string
    {
        if (is_null($parentId)) {
            return null;
        }

        if ($parentId === $id) {
            return "Quest \"{$this->nameFor($id)}\" cannot be its own parent.";
        }

        if ($this->walks($parentId, $id, fn (int $currentId) => $this->parentEdge($currentId))) {
            return "The parent selected for \"{$this->nameFor($id)}\" would create a Quest chain cycle.";
        }

        return null;
    }

    /**
     * Resolve the required-Quest-selection error for one workbook row, when its selection is invalid.
     */
    private function requiredQuestError(int $id, ?int $requiredId): ?string
    {
        if (is_null($requiredId)) {
            return null;
        }

        if ($requiredId === $id) {
            return "Quest \"{$this->nameFor($id)}\" cannot require itself.";
        }

        if ($this->walks($requiredId, $id, fn (int $currentId) => $this->requiredEdge($currentId))) {
            return "The required Quest selected for \"{$this->nameFor($id)}\" would create a required-Quest cycle.";
        }

        return null;
    }

    /**
     * Resolve the required-Quest-chain error for one workbook row, when its chain is invalid.
     */
    private function requiredChainError(int $id, array $chainIds): ?string
    {
        if (empty($chainIds)) {
            return null;
        }

        if (count($chainIds) !== count(array_unique($chainIds))) {
            return "The required Quest chain for \"{$this->nameFor($id)}\" contains duplicate Quests.";
        }

        if (in_array($id, $chainIds, true)) {
            return "Quest \"{$this->nameFor($id)}\" cannot require itself in its own required Quest chain.";
        }

        foreach ($chainIds as $chainId) {
            if ($this->chainTransitivelyRequires($chainId, $id, [])) {
                return "The required Quest chain for \"{$this->nameFor($id)}\" would create a circular requirement.";
            }
        }

        return null;
    }

    /**
     * Walk a Quest's required-Quest and required-Quest-chain links, looking for a target id.
     */
    private function chainTransitivelyRequires(int $currentId, int $targetId, array $visited): bool
    {
        if (in_array($currentId, $visited, true)) {
            return false;
        }

        $visited[] = $currentId;

        $linkedIds = array_filter(
            array_merge([$this->requiredEdge($currentId)], $this->chainEdge($currentId)),
            fn (?int $linkedId) => ! is_null($linkedId)
        );

        foreach ($linkedIds as $linkedId) {
            if ($linkedId === $targetId || $this->chainTransitivelyRequires($linkedId, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Walk a single-edge chain starting from an id, looking for a target id.
     */
    private function walks(int $startId, int $targetId, callable $edgeResolver): bool
    {
        $visited = [];
        $currentId = $startId;

        while (! is_null($currentId)) {
            if ($currentId === $targetId) {
                return true;
            }

            if (in_array($currentId, $visited, true)) {
                return false;
            }

            $visited[] = $currentId;
            $currentId = $edgeResolver($currentId);
        }

        return false;
    }

    /**
     * Resolve a Quest id's `parent_quest_id` edge, preferring the workbook's own resolved value.
     */
    private function parentEdge(int $id): ?int
    {
        if (array_key_exists($id, $this->rowsById)) {
            return $this->rowsById[$id]['parent_quest_id'];
        }

        return $this->quest($id)?->parent_quest_id;
    }

    /**
     * Resolve a Quest id's `required_quest_id` edge, preferring the workbook's own resolved value.
     */
    private function requiredEdge(int $id): ?int
    {
        if (array_key_exists($id, $this->rowsById)) {
            return $this->rowsById[$id]['required_quest_id'];
        }

        return $this->quest($id)?->required_quest_id;
    }

    /**
     * Resolve a Quest id's `required_quest_chain` edge, preferring the workbook's own resolved value.
     */
    private function chainEdge(int $id): array
    {
        if (array_key_exists($id, $this->rowsById)) {
            return $this->rowsById[$id]['required_quest_chain'];
        }

        return $this->quest($id)?->required_quest_chain ?? [];
    }

    /**
     * Resolve and memoize a persisted Quest for graph validation.
     */
    private function quest(int $id): ?Quest
    {
        if ($id < 0) {
            return null;
        }

        if (! array_key_exists($id, $this->questCache)) {
            $this->questCache[$id] = Quest::find($id);
        }

        return $this->questCache[$id];
    }

    /**
     * Resolve a Quest id's display name for an error message.
     */
    private function nameFor(int $id): string
    {
        $name = array_search($id, $this->nameToId, true);

        return $name === false ? "#{$id}" : $name;
    }
}
