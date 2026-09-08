<?php

namespace App\Admin\Quests\Services;

use App\Admin\Quests\Requests\StoreQuestRequest;
use App\Admin\Quests\Requests\UpdateQuestRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Game\Core\Items\Values\ItemCatalogType;

class QuestService
{
    /**
     * Create a new Quest from the validated form data.
     */
    public function create(StoreQuestRequest $request): Quest
    {
        $quest = Quest::create($this->normalize($request->validated()));

        $this->reconcileParentFlags(null, $quest->parent_quest_id);

        return $quest;
    }

    /**
     * Update an existing Quest from the validated form data.
     */
    public function update(Quest $quest, UpdateQuestRequest $request): Quest
    {
        $previousParentId = $quest->parent_quest_id;

        $quest->update($this->normalize($request->validated()));

        $this->reconcileParentFlags($previousParentId, $quest->parent_quest_id);

        return $quest->refresh();
    }

    /**
     * Build the internal Admin Quest form option data.
     */
    public function formOptions(): array
    {
        return [
            'npcs' => Npc::orderBy('real_name')->orderBy('id')->get(),
            'quest_items' => Item::where('type', ItemCatalogType::QUEST->value)->orderBy('name')->orderBy('id')->get(),
            'quests' => Quest::orderBy('name')->orderBy('id')->get(),
            'game_maps' => GameMap::orderBy('name')->orderBy('id')->get(),
            'raids' => Raid::orderBy('name')->orderBy('id')->get(),
            'passive_skills' => PassiveSkill::orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Apply cross-field normalization rules to validated Quest data before persistence.
     */
    public function normalize(array $data): array
    {
        if (! ($data['unlocks_skill'] ?? false)) {
            $data['unlocks_skill_type'] = null;
        }

        if (is_null($data['item_id'] ?? null)) {
            $data['secondary_required_item'] = null;
        }

        return $data;
    }

    /**
     * Reconcile Quest parent flags with the persisted parent-child hierarchy.
     */
    public function reconcileParentFlags(?int $previousParentId, ?int $newParentId): void
    {
        if (! is_null($newParentId)) {
            Quest::where('id', $newParentId)
                ->where('is_parent', false)
                ->update(['is_parent' => true]);
        }

        if (is_null($previousParentId) || $previousParentId === $newParentId) {
            return;
        }

        if (! Quest::where('parent_quest_id', $previousParentId)->exists()) {
            Quest::where('id', $previousParentId)->update(['is_parent' => false]);
        }
    }
}
