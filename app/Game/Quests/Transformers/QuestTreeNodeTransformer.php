<?php

namespace App\Game\Quests\Transformers;

use App\Flare\Models\Quest;
use App\Game\Quests\Values\QuestKind;

class QuestTreeNodeTransformer
{
    /**
     * Transform a Quest and its already-built child nodes into a factual Quest tree node.
     *
     * @param  Quest  $quest  Quest to transform; expects `npc.gameMap` and `raid` eager-loaded.
     * @param  QuestKind  $kind  Already-resolved factual Quest kind.
     * @param  array<int, array<string, mixed>>  $children  Already-built, already-sorted child tree nodes.
     * @return array{id: int, name: string, kind: string, parent_quest_id: int|null, required_quest_id: int|null, required_quest_chain_ids: array<int, int>, npc: array{id: int, name: string}|null, game_map: array{id: int, name: string}|null, raid: array{id: int, name: string}|null, only_for_event: int|null, children: array<int, array<string, mixed>>} Factual Quest tree node.
     */
    public function transform(Quest $quest, QuestKind $kind, array $children): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'kind' => $kind->value,
            'parent_quest_id' => $quest->parent_quest_id,
            'required_quest_id' => $quest->required_quest_id,
            'required_quest_chain_ids' => $quest->required_quest_chain ?? [],
            'npc' => $this->npcIdentity($quest),
            'game_map' => $this->gameMapIdentity($quest),
            'raid' => $this->raidIdentity($quest),
            'only_for_event' => $quest->only_for_event,
            'children' => $children,
        ];
    }

    /**
     * Build the compact factual identity of the Quest's giver NPC.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{id: int, name: string}|null Quest giver NPC identity.
     */
    private function npcIdentity(Quest $quest): ?array
    {
        if (is_null($quest->npc)) {
            return null;
        }

        return [
            'id' => $quest->npc->id,
            'name' => $quest->npc->real_name,
        ];
    }

    /**
     * Build the compact factual identity of the Quest giver's owning Game Map.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{id: int, name: string}|null Quest giver's Game Map identity.
     */
    private function gameMapIdentity(Quest $quest): ?array
    {
        if (is_null($quest->npc) || is_null($quest->npc->gameMap)) {
            return null;
        }

        return [
            'id' => $quest->npc->gameMap->id,
            'name' => $quest->npc->gameMap->name,
        ];
    }

    /**
     * Build the compact factual identity of the Quest's Raid, when it belongs to one.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{id: int, name: string}|null Raid identity.
     */
    private function raidIdentity(Quest $quest): ?array
    {
        if (is_null($quest->raid)) {
            return null;
        }

        return [
            'id' => $quest->raid->id,
            'name' => $quest->raid->name,
        ];
    }
}
