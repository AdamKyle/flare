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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemService
{
    /**
     * Paginate the catalog Items list for the validated Admin index request.
     *
     * @param  ItemIndexRequest  $request  Validated Item list request.
     * @return LengthAwarePaginator Paginated catalog Item records.
     */
    public function paginate(ItemIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $profile = ItemProfile::from($request->validated('profile'));
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
     *
     * @return array{item_skills: Collection<int, ItemSkill>, locations: Collection<int, Location>, classes: Collection<int, GameClass>} Internal Item form option data.
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
     *
     * @param  StoreItemRequest  $request  Validated Item creation request.
     * @return Item Created Item.
     */
    public function create(StoreItemRequest $request): Item
    {
        return Item::create($this->normalize($request->validated()));
    }

    /**
     * Update an existing catalog Item from the validated form data.
     *
     * @param  Item  $item  Item to update.
     * @param  UpdateItemRequest  $request  Validated Item update request.
     * @return Item Updated Item.
     */
    public function update(Item $item, UpdateItemRequest $request): Item
    {
        $item->update($this->normalize($request->validated()));

        return $item->refresh();
    }

    /**
     * Apply the current 2.0 cross-field catalog normalization rules to validated Item data.
     *
     * Shared by the Store/Update form path and the Item import Sheet so both mutation paths apply
     * the exact same cross-field business rules.
     *
     * @param  array<string, mixed>  $data  Validated Item form data.
     * @return array<string, mixed> Normalized Item attributes.
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
     *
     * A catalog Item is only safe to delete when nothing currently
     * references it: player inventories/sets, market activity, Item Skill
     * progression, holy stacks, sockets, generated child Items, Quest/
     * Location relationships, Monster/Raid/Guide Quest rewards or
     * requirements, character Boons, Alchemy bag slots, Faction Loyalty
     * automation failed-crafting state, global event crafting inventory,
     * and character battle reward messages must all be free of the Item.
     *
     * @param  Item  $item  Item to audit.
     * @return array{deletable: bool, blockers: array<int, string>} Deletion safety result.
     */
    public function deletionBlockers(Item $item): array
    {
        $blockers = [];

        if (InventorySlot::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is held in a character inventory or equipment slot.';
        }

        if (SetSlot::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is assigned to a character inventory set.';
        }

        if (MarketBoard::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item has an active market listing.';
        }

        if (MarketHistory::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item has market sale history.';
        }

        if (ItemSkillProgression::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item has Item Skill progression recorded against it.';
        }

        if (ItemSocket::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item has sockets recorded against it.';
        }

        if (HolyStack::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item has holy stacks recorded against it.';
        }

        if (Item::where('parent_id', $item->id)->exists()) {
            $blockers[] = 'This Item has generated child Items.';
        }

        if (! is_null($item->parent_id)) {
            $blockers[] = 'This Item is a generated variant of another Item.';
        }

        if (Quest::where('item_id', $item->id)
            ->orWhere('secondary_required_item', $item->id)
            ->orWhere('reward_item', $item->id)
            ->exists()) {
            $blockers[] = 'This Item is referenced by a Quest.';
        }

        if (Location::where('quest_reward_item_id', $item->id)
            ->orWhere('required_quest_item_id', $item->id)
            ->exists()) {
            $blockers[] = 'This Item is referenced by a Location quest reward or requirement.';
        }

        if (Monster::where('quest_item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is referenced by a Monster quest Item drop.';
        }

        if (Raid::where('artifact_item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is referenced by a Raid artifact reward.';
        }

        if (GuideQuest::where('required_quest_item_id', $item->id)
            ->orWhere('secondary_quest_item_id', $item->id)
            ->exists()) {
            $blockers[] = 'This Item is referenced by a Guide Quest requirement.';
        }

        if (CharacterBoon::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is referenced by a character Boon.';
        }

        if (AlchemyBagSlot::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is held in a character Alchemy bag slot.';
        }

        if (FactionLoyaltyAutomation::where('failed_crafting_item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is referenced by Faction Loyalty automation failed-crafting state.';
        }

        if (GlobalEventCraftingInventorySlot::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is held in a global event crafting inventory slot.';
        }

        if (CharacterBattleRewardRequestMessage::where('item_id', $item->id)->exists()) {
            $blockers[] = 'This Item is referenced by a character battle reward message.';
        }

        return [
            'deletable' => empty($blockers),
            'blockers' => $blockers,
        ];
    }

    /**
     * Delete the given catalog Item after confirming it has no dependencies.
     *
     * @param  Item  $item  Item to delete.
     * @return array{deletable: bool, blockers: array<int, string>} Deletion result; the Item is deleted only when safe.
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
     * Build the base catalog query, excluding generated/affixed Item instances.
     *
     * @return Builder Query scoped to base catalog Items only.
     */
    private function catalogQuery(): Builder
    {
        return Item::query()
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('parent_id');
    }
}
