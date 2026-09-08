<?php

namespace App\Admin\Items\Services;

use App\Admin\Items\Requests\ItemIndexRequest;
use App\Admin\Items\Requests\StoreItemRequest;
use App\Admin\Items\Requests\UpdateItemRequest;
use App\Admin\Items\Values\ItemProfile;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\GameClass;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\HolyStack;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\ItemSkillProgression;
use App\Flare\Models\ItemSocket;
use App\Flare\Models\Location;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Flare\Models\SetSlot;
use App\Game\Core\Items\Values\ItemCatalogType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemService
{
    /**
     * Paginate the catalog Items list for the validated Admin index request.
     */
    public function paginate(ItemIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $profile = ItemProfile::from($request->validated('profile'));
        $subtype = $request->validated('subtype');
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');

        $query = $this->catalogQuery();

        if (! empty($searchText)) {
            $query->where('name', 'LIKE', '%'.$searchText.'%');
        }

        $types = $profile->types();

        if (! is_null($types)) {
            $query->whereIn('type', $types);
        }

        if (! is_null($subtype)) {
            $query->where('type', $subtype);
        }

        if ($profile->requiresSpecialtyType()) {
            $query->whereNotNull('specialty_type');
        }

        $query->orderBy($sortKey, $sortDirection)
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin form option data for Item management.
     */
    public function formOptions(): array
    {
        return [
            'item_skills' => ItemSkill::whereNull('parent_id')->orderBy('name')->get(),
            'locations' => Location::orderBy('name')->orderBy('id')->get(),
            'classes' => GameClass::orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Create a new catalog Item from the validated form data.
     */
    public function create(StoreItemRequest $request): Item
    {
        return Item::create($this->normalize($request->validated()));
    }

    /**
     * Update an existing catalog Item from the validated form data.
     */
    public function update(Item $item, UpdateItemRequest $request): Item
    {
        $item->update($this->normalize($request->validated()));

        return $item->refresh();
    }

    /**
     * Apply the current 2.0 cross-field catalog normalization rules to validated Item data.
     */
    public function normalize(array $data): array
    {
        if (($data['type'] ?? null) !== ItemCatalogType::QUEST->value) {
            $data['effect'] = null;
        }

        if (! ($data['can_use_on_other_items'] ?? false)) {
            $data['holy_level'] = null;
        }

        if (! ($data['usable'] ?? false)) {
            $data['lasts_for'] = null;
            $data['damages_kingdoms'] = false;
            $data['stat_increase'] = false;
            $data['affects_skill_type'] = null;
        }

        if (! ($data['damages_kingdoms'] ?? false)) {
            $data['kingdom_damage'] = null;
        } else {
            $data['lasts_for'] = null;
            $data['stat_increase'] = false;
            $data['affects_skill_type'] = null;
        }

        if (! ($data['stat_increase'] ?? false)) {
            $data['increase_stat_by'] = null;
        }

        if (is_null($data['affects_skill_type'] ?? null)) {
            $data['increase_skill_bonus_by'] = null;
            $data['increase_skill_training_bonus_by'] = null;
        }

        if (! ($data['can_resurrect'] ?? false)) {
            $data['resurrection_chance'] = null;
        }

        if (! ($data['can_craft'] ?? false)) {
            $data['crafting_type'] = null;
            $data['craft_only'] = false;
            $data['skill_level_required'] = null;
            $data['skill_level_trivial'] = null;
        }

        return $data;
    }

    /**
     * Determine whether the given catalog Item can be safely deleted.
     */
    public function deletionBlockers(Item $item): array
    {
        $activeCategories = collect($this->blockerCategories($item))
            ->filter(fn (array $category) => $category['count'] > 0);

        return [
            'deletable' => $activeCategories->isEmpty(),
            'blockers' => $activeCategories->pluck('label')->values()->all(),
        ];
    }

    /**
     * Build the read-only Item deletion-impact report.
     */
    public function usage(Item $item): array
    {
        $categories = $this->blockerCategories($item);
        $activeCategories = collect($categories)->filter(fn (array $category) => $category['count'] > 0);

        return [
            'deletable' => $activeCategories->isEmpty(),
            'total_blocker_categories' => $activeCategories->count(),
            'blockers' => $activeCategories->values()->all(),
        ];
    }

    /**
     * Delete the given catalog Item after confirming it has no dependencies.
     */
    public function delete(Item $item): array
    {
        $safety = $this->deletionBlockers($item);

        if ($safety['deletable']) {
            $item->delete();
        }

        return $safety;
    }

    /**
     * Build the Item deletion-blocker categories used by deletion checks and usage reporting.
     */
    private function blockerCategories(Item $item): array
    {
        return [
            $this->countCategory('inventory_slots', 'This Item is held in a character inventory or equipment slot.', InventorySlot::where('item_id', $item->id)->count()),
            $this->countCategory('set_slots', 'This Item is assigned to a character inventory set.', SetSlot::where('item_id', $item->id)->count()),
            $this->countCategory('market_listings', 'This Item has an active market listing.', MarketBoard::where('item_id', $item->id)->count()),
            $this->countCategory('market_history', 'This Item has market sale history.', MarketHistory::where('item_id', $item->id)->count()),
            $this->countCategory('item_skill_progression', 'This Item has Item Skill progression recorded against it.', ItemSkillProgression::where('item_id', $item->id)->count()),
            $this->countCategory('item_sockets', 'This Item has sockets recorded against it.', ItemSocket::where('item_id', $item->id)->count()),
            $this->countCategory('holy_stacks', 'This Item has holy stacks recorded against it.', HolyStack::where('item_id', $item->id)->count()),
            $this->countCategory('generated_child_items', 'This Item has generated child Items.', Item::where('parent_id', $item->id)->count()),
            $this->countCategory('generated_parent_item', 'This Item is a generated variant of another Item.', is_null($item->parent_id) ? 0 : 1),
            $this->questBlockerCategory($item),
            $this->locationBlockerCategory($item),
            $this->monsterBlockerCategory($item),
            $this->raidBlockerCategory($item),
            $this->guideQuestBlockerCategory($item),
            $this->countCategory('character_boons', 'This Item is referenced by a character Boon.', CharacterBoon::where('item_id', $item->id)->count()),
            $this->countCategory('alchemy_bag_slots', 'This Item is held in a character Alchemy bag slot.', AlchemyBagSlot::where('item_id', $item->id)->count()),
            $this->countCategory('faction_loyalty_automation', 'This Item is referenced by Faction Loyalty automation failed-crafting state.', FactionLoyaltyAutomation::where('failed_crafting_item_id', $item->id)->count()),
            $this->countCategory('global_event_crafting_inventory', 'This Item is held in a global event crafting inventory slot.', GlobalEventCraftingInventorySlot::where('item_id', $item->id)->count()),
            $this->countCategory('battle_reward_messages', 'This Item is referenced by a character battle reward message.', CharacterBattleRewardRequestMessage::where('item_id', $item->id)->count()),
        ];
    }

    /**
     * Build a count-only blocker category with no related entity identities.
     */
    private function countCategory(string $key, string $label, int $count): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
        ];
    }

    /**
     * Build the Quest blocker category, including the referencing Quests' stable factual identities.
     */
    private function questBlockerCategory(Item $item): array
    {
        $quests = Quest::where('item_id', $item->id)
            ->orWhere('secondary_required_item', $item->id)
            ->orWhere('reward_item', $item->id)
            ->orderBy('name')
            ->get();

        return [
            'key' => 'quests',
            'label' => 'This Item is referenced by a Quest.',
            'count' => $quests->count(),
            'related_entities' => $quests->map(fn (Quest $quest) => [
                'id' => $quest->id,
                'name' => $quest->name,
                'resource' => 'quest',
            ])->values()->all(),
        ];
    }

    /**
     * Build the Location blocker category, including the referencing Locations' stable factual identities.
     */
    private function locationBlockerCategory(Item $item): array
    {
        $locations = Location::where('quest_reward_item_id', $item->id)
            ->orWhere('required_quest_item_id', $item->id)
            ->orderBy('name')
            ->get();

        return [
            'key' => 'locations',
            'label' => 'This Item is referenced by a Location quest reward or requirement.',
            'count' => $locations->count(),
            'related_entities' => $locations->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'resource' => 'location',
            ])->values()->all(),
        ];
    }

    /**
     * Build the Monster blocker category, including the referencing Monsters' stable factual identities.
     */
    private function monsterBlockerCategory(Item $item): array
    {
        $monsters = Monster::where('quest_item_id', $item->id)->orderBy('name')->get();

        return [
            'key' => 'monster_drops',
            'label' => 'This Item is referenced by a Monster quest Item drop.',
            'count' => $monsters->count(),
            'related_entities' => $monsters->map(fn (Monster $monster) => [
                'id' => $monster->id,
                'name' => $monster->name,
                'resource' => 'monster',
            ])->values()->all(),
        ];
    }

    /**
     * Build the Raid blocker category, including the referencing Raids' stable factual identities.
     */
    private function raidBlockerCategory(Item $item): array
    {
        $raids = Raid::where('artifact_item_id', $item->id)->orderBy('name')->get();

        return [
            'key' => 'raid_artifact',
            'label' => 'This Item is referenced by a Raid artifact reward.',
            'count' => $raids->count(),
            'related_entities' => $raids->map(fn (Raid $raid) => [
                'id' => $raid->id,
                'name' => $raid->name,
                'resource' => 'raid',
            ])->values()->all(),
        ];
    }

    /**
     * Build the Guide Quest blocker category, including the referencing Guide Quests' stable factual identities.
     */
    private function guideQuestBlockerCategory(Item $item): array
    {
        $guideQuests = GuideQuest::where('required_quest_item_id', $item->id)
            ->orWhere('secondary_quest_item_id', $item->id)
            ->orderBy('name')
            ->get();

        return [
            'key' => 'guide_quests',
            'label' => 'This Item is referenced by a Guide Quest requirement.',
            'count' => $guideQuests->count(),
            'related_entities' => $guideQuests->map(fn (GuideQuest $guideQuest) => [
                'id' => $guideQuest->id,
                'name' => $guideQuest->name,
                'resource' => 'guide_quest',
            ])->values()->all(),
        ];
    }

    /**
     * Build the base catalog query, excluding generated/affixed Item instances.
     */
    private function catalogQuery(): Builder
    {
        return Item::query()
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('parent_id');
    }
}
