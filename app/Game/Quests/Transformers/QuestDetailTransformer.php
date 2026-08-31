<?php

namespace App\Game\Quests\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Quests\Values\QuestKind;

class QuestDetailTransformer
{
    /**
     * @param  QuestItemTransformer  $questItemTransformer  Canonical quest Item factual transformer, shared with the Admin Item detail presentation.
     */
    public function __construct(
        private readonly QuestItemTransformer $questItemTransformer,
    ) {}

    /**
     * Transform a Quest into its full factual detail representation.
     *
     * @param  Quest  $quest  Quest to transform; expects its full relation set eager-loaded via `loadRelations()` plus `childQuests`, `raid`, `factionLoyaltyNpc.gameMap`.
     * @param  QuestKind  $kind  Already-resolved factual Quest kind.
     * @param  array<int, array{id: int, name: string}>  $requiredQuestChain  Already-resolved required Quest chain identities, in stored order.
     * @param  array{id: int, name: string, type: int}|null  $unlockedSkill  Already-resolved unlocked Game Skill identity.
     * @return array<string, mixed> Full factual Quest detail representation.
     */
    public function transform(Quest $quest, QuestKind $kind, array $requiredQuestChain, ?array $unlockedSkill): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'kind' => $kind->value,
            'story' => $this->story($quest),
            'npc' => $this->npc($quest),
            'structure' => $this->structure($quest, $requiredQuestChain),
            'availability' => $this->availability($quest),
            'requirements' => $this->requirements($quest),
            'rewards' => $this->rewards($quest, $unlockedSkill),
        ];
    }

    /**
     * Build the raw Markdown story section.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{before_completion_markdown: string|null, after_completion_markdown: string|null} Raw Markdown story text.
     */
    private function story(Quest $quest): array
    {
        return [
            'before_completion_markdown' => $quest->before_completion_description,
            'after_completion_markdown' => $quest->after_completion_description,
        ];
    }

    /**
     * Build the Quest giver NPC section.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{id: int, name: string, type: int, x_position: int, y_position: int, must_be_at_same_location: bool, game_map: array{id: int, name: string}|null}|null Quest giver NPC section.
     */
    private function npc(Quest $quest): ?array
    {
        if (is_null($quest->npc)) {
            return null;
        }

        $npc = $quest->npc;

        return [
            'id' => $npc->id,
            'name' => $npc->real_name,
            'type' => $npc->type,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
            'must_be_at_same_location' => $npc->must_be_at_same_location,
            'game_map' => is_null($npc->gameMap) ? null : [
                'id' => $npc->gameMap->id,
                'name' => $npc->gameMap->name,
            ],
        ];
    }

    /**
     * Build the hierarchy/dependency structure section.
     *
     * @param  Quest  $quest  Quest to describe.
     * @param  array<int, array{id: int, name: string}>  $requiredQuestChain  Already-resolved required Quest chain identities, in stored order.
     * @return array<string, mixed> Structure section.
     */
    private function structure(Quest $quest, array $requiredQuestChain): array
    {
        return [
            'parent_quest' => $this->questIdentity($quest->parent),
            'child_quests' => $quest->childQuests->map(fn (Quest $child) => $this->questIdentity($child))->values()->all(),
            'required_quest' => $this->questIdentity($quest->requiredQuest),
            'required_quest_chain' => $requiredQuestChain,
            'compatibility_parent_chain_quest_id' => $quest->parent_chain_quest_id,
        ];
    }

    /**
     * Build the availability section.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{raid: array{id: int, name: string}|null, only_for_event: int|null} Availability section.
     */
    private function availability(Quest $quest): array
    {
        return [
            'raid' => is_null($quest->raid) ? null : ['id' => $quest->raid->id, 'name' => $quest->raid->name],
            'only_for_event' => $quest->only_for_event,
        ];
    }

    /**
     * Build the requirements section.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array<string, mixed> Requirements section.
     */
    private function requirements(Quest $quest): array
    {
        return [
            'primary_item' => $this->questItem($quest->item),
            'secondary_item' => $this->questItem($quest->secondaryItem),
            'reincarnated_times' => $quest->reincarnated_times,
            'access_to_map' => is_null($quest->requiredPlane) ? null : [
                'id' => $quest->requiredPlane->id,
                'name' => $quest->requiredPlane->name,
            ],
            'faction' => $this->faction($quest),
            'faction_loyalty' => $this->factionLoyalty($quest),
            'currencies' => [
                'gold' => $quest->gold_cost,
                'gold_dust' => $quest->gold_dust_cost,
                'shards' => $quest->shard_cost,
                'copper_coins' => $quest->copper_coin_cost,
            ],
        ];
    }

    /**
     * Build the faction Map requirement, when the Quest has one.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{game_map: array{id: int, name: string}, required_level: int|null}|null Faction requirement.
     */
    private function faction(Quest $quest): ?array
    {
        if (is_null($quest->factionMap)) {
            return null;
        }

        return [
            'game_map' => ['id' => $quest->factionMap->id, 'name' => $quest->factionMap->name],
            'required_level' => $quest->required_faction_level,
        ];
    }

    /**
     * Build the Faction Loyalty assisting NPC requirement, when the Quest has one.
     *
     * @param  Quest  $quest  Quest to describe.
     * @return array{npc: array{id: int, name: string, game_map: array{id: int, name: string}|null}, required_fame_level: int|null}|null Faction Loyalty requirement.
     */
    private function factionLoyalty(Quest $quest): ?array
    {
        if (is_null($quest->factionLoyaltyNpc)) {
            return null;
        }

        $npc = $quest->factionLoyaltyNpc;

        return [
            'npc' => [
                'id' => $npc->id,
                'name' => $npc->real_name,
                'game_map' => is_null($npc->gameMap) ? null : ['id' => $npc->gameMap->id, 'name' => $npc->gameMap->name],
            ],
            'required_fame_level' => $quest->required_fame_level,
        ];
    }

    /**
     * Build the rewards section.
     *
     * @param  Quest  $quest  Quest to describe.
     * @param  array{id: int, name: string, type: int}|null  $unlockedSkill  Already-resolved unlocked Game Skill identity.
     * @return array<string, mixed> Rewards section.
     */
    private function rewards(Quest $quest, ?array $unlockedSkill): array
    {
        return [
            'item' => $this->questItem($quest->rewardItem),
            'gold' => $quest->reward_gold,
            'gold_dust' => $quest->reward_gold_dust,
            'shards' => $quest->reward_shards,
            'xp' => $quest->reward_xp,
            'skill' => $unlockedSkill,
            'feature' => $quest->unlocks_feature,
            'passive' => is_null($quest->passive) ? null : ['id' => $quest->passive->id, 'name' => $quest->passive->name],
        ];
    }

    /**
     * Build the compact factual identity of a related Quest.
     *
     * @param  Quest|null  $quest  Related Quest, when one is set.
     * @return array{id: int, name: string}|null Compact Quest identity.
     */
    private function questIdentity(?Quest $quest): ?array
    {
        if (is_null($quest)) {
            return null;
        }

        return ['id' => $quest->id, 'name' => $quest->name];
    }

    /**
     * Delegate to the canonical quest Item factual transformer.
     *
     * @param  Item|null  $item  Quest Item, when one is set.
     * @return array<string, mixed>|null Canonical quest Item factual payload.
     */
    private function questItem(?Item $item): ?array
    {
        if (is_null($item)) {
            return null;
        }

        return $this->questItemTransformer->transform($item);
    }
}
