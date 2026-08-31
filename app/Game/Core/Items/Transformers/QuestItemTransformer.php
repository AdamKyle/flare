<?php

namespace App\Game\Core\Items\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use Facades\App\Game\Core\Items\Presenters\QuestItemEffectsPresenter;
use League\Fractal\TransformerAbstract;

class QuestItemTransformer extends TransformerAbstract
{
    /**
     * Transform a quest Item into its canonical factual payload, including
     * navigable relationship identities for every Map, Location, NPC,
     * Quest, and Monster connected to the Item.
     */
    public function transform(Item $item): array
    {
        return [
            'item_id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'description' => $item->description,
            'can_drop' => $item->can_drop,
            'usable' => $item->usable,
            'craft_only' => $item->craft_only,
            'move_time_out_mod_bonus' => $item->move_time_out_mod_bonus,
            'fight_time_out_mod_bonus' => $item->fight_time_out_mod_bonus,
            'effect' => QuestItemEffectsPresenter::getEffect($item->effect),
            'drop_location' => $this->getDropLocation($item),
            'required_monsters' => $this->getRequiredMonsters($item),
            'required_quest' => $this->getRequiredQuest($item),
            'reward_locations' => $this->getRewardLocations($item),
            'required_quests' => $this->getRequiredQuests($item),
            'reward_quests' => $this->getRewardQuests($item),
            'required_locations' => $this->getRequiredLocations($item),
        ];
    }

    /**
     * Build the factual Location identity for the Item's drop Location.
     */
    private function getDropLocation(Item $item): ?array
    {
        if (! $item->relationLoaded('dropLocation')) {
            $item->load('dropLocation.map');
        }

        if (! $item->dropLocation) {
            return null;
        }

        return $this->buildLocationIdentity($item->dropLocation);
    }

    /**
     * Build the deterministically ordered factual Monster identities for
     * every Monster that drops this Item, preserving every match instead of
     * only the first.
     */
    private function getRequiredMonsters(Item $item): array
    {
        return Monster::where('quest_item_id', $item->id)
            ->with('gameMap')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Monster $monster) => $this->buildMonsterIdentity($monster))
            ->all();
    }

    /**
     * Build the factual Quest identity for the Item's required Quest.
     */
    private function getRequiredQuest(Item $item): ?array
    {
        $quest = $item->required_quest;

        if (is_null($quest)) {
            return null;
        }

        return $this->buildQuestIdentity($quest);
    }

    /**
     * Build the factual Location identities for every Location this Item
     * is a reward at.
     */
    private function getRewardLocations(Item $item): array
    {
        return collect($item->locations)
            ->map(fn (Location $location) => $this->buildLocationIdentity($location))
            ->all();
    }

    /**
     * Build the factual Quest identities for every Quest that requires
     * this Item as a primary or secondary requirement.
     */
    private function getRequiredQuests(Item $item): array
    {
        return Quest::where('item_id', $item->id)
            ->orWhere('secondary_required_item', $item->id)
            ->with('npc.gameMap')
            ->get()
            ->map(fn (Quest $quest) => $this->buildQuestIdentity($quest))
            ->toArray();
    }

    /**
     * Build the factual Quest identities for every Quest that rewards this
     * Item.
     */
    private function getRewardQuests(Item $item): array
    {
        return Quest::where('reward_item', $item->id)
            ->with('npc.gameMap')
            ->get()
            ->map(fn (Quest $quest) => $this->buildQuestIdentity($quest))
            ->toArray();
    }

    /**
     * Build the factual Location identities for every Location that
     * requires this Item.
     */
    private function getRequiredLocations(Item $item): array
    {
        return Location::where('required_quest_item_id', $item->id)
            ->with('map')
            ->get()
            ->map(fn (Location $location) => $this->buildLocationIdentity($location))
            ->toArray();
    }

    /**
     * Build the canonical factual Location identity, including its owning
     * Game Map identity.
     */
    private function buildLocationIdentity(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'game_map' => [
                'id' => $location->map->id,
                'name' => $location->map->name,
            ],
        ];
    }

    /**
     * Build the canonical factual NPC identity, including its owning Game
     * Map identity.
     */
    private function buildNpcIdentity(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'name' => $npc->real_name,
            'type' => $npc->type,
            'game_map' => [
                'id' => $npc->gameMap->id,
                'name' => $npc->gameMap->name,
            ],
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }

    /**
     * Build the canonical factual Quest identity, including its Quest
     * Giver NPC identity and the NPC's owning Game Map identity.
     */
    private function buildQuestIdentity(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'npc' => is_null($quest->npc) ? null : $this->buildNpcIdentity($quest->npc),
            'game_map' => is_null($quest->npc) ? null : [
                'id' => $quest->npc->gameMap->id,
                'name' => $quest->npc->gameMap->name,
            ],
        ];
    }

    /**
     * Build the canonical factual Monster identity, including its owning
     * Game Map identity and its quest Item drop chance.
     */
    private function buildMonsterIdentity(Monster $monster): array
    {
        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'game_map' => [
                'id' => $monster->gameMap->id,
                'name' => $monster->gameMap->name,
            ],
            'quest_item_drop_chance' => $monster->quest_item_drop_chance,
        ];
    }
}
