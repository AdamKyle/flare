<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\GameClass;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use Illuminate\Support\Collection;

class SkillBonusContextService
{
    private array $inventoryIdByCharacterId = [];

    private array $equippedSlotsByCharacterId = [];

    private array $questSlotsByInventoryIdAndSkillName = [];

    private array $boonsByCharacterId = [];

    private array $classById = [];

    /**
     * Forget every cached bonus context so the next lookup reads current Character state.
     *
     * @return void
     */
    public function clearCachedContext(): void
    {
        $this->inventoryIdByCharacterId = [];
        $this->equippedSlotsByCharacterId = [];
        $this->questSlotsByInventoryIdAndSkillName = [];
        $this->boonsByCharacterId = [];
        $this->classById = [];
    }

    /**
     * Return the equipped slots, with items, of the Character owning the Skill.
     *
     * @param Skill $skill
     * @return Collection
     */
    public function equippedSlotsWithItems(Skill $skill): Collection
    {
        $characterId = $skill->character_id;

        if (array_key_exists($characterId, $this->equippedSlotsByCharacterId)) {
            return $this->equippedSlotsByCharacterId[$characterId];
        }

        $slots = $this->equippedSlotsFromLoadedInventory($skill) ?? $this->queryEquippedSlots($skill);

        $this->equippedSlotsByCharacterId[$characterId] = $slots;

        return $slots;
    }

    /**
     * Return the inventory quest slots, with items, that name the Skill.
     *
     * @param Skill $skill
     * @return Collection
     */
    public function questSlotsWithItems(Skill $skill): Collection
    {
        $inventoryId = $this->inventoryId($skill);

        if (is_null($inventoryId)) {
            return collect();
        }

        $skillName = $skill->baseSkill->name;
        $questSlotsKey = $inventoryId.'|'.$skillName;

        if (array_key_exists($questSlotsKey, $this->questSlotsByInventoryIdAndSkillName)) {
            return $this->questSlotsByInventoryIdAndSkillName[$questSlotsKey];
        }

        $slots = $this->questSlotsFromLoadedInventory($skill, $skillName) ?? $this->queryQuestSlots($inventoryId, $skillName);

        $this->questSlotsByInventoryIdAndSkillName[$questSlotsKey] = $slots;

        return $slots;
    }

    /**
     * Return the active boons, with their used items, of the Character owning the Skill.
     *
     * @param Skill $skill
     * @return Collection
     */
    public function activeBoonsWithItemUsed(Skill $skill): Collection
    {
        $character = $skill->character;

        if (is_null($character)) {
            return collect();
        }

        $characterId = $character->id;

        if (array_key_exists($characterId, $this->boonsByCharacterId)) {
            return $this->boonsByCharacterId[$characterId];
        }

        $boons = $this->activeBoonsFromLoadedRelations($character) ?? CharacterBoon::query()
            ->active()
            ->where('character_id', $characterId)
            ->with('itemUsed')
            ->get();

        $this->boonsByCharacterId[$characterId] = $boons;

        return $boons;
    }

    /**
     * Return the Game Class of the Character.
     *
     * @param Character $character
     * @return GameClass|null
     */
    public function gameClass(Character $character): ?GameClass
    {
        $classId = $character->game_class_id;

        if (array_key_exists($classId, $this->classById)) {
            return $this->classById[$classId];
        }

        $class = $this->loadedGameClass($character) ?? GameClass::query()->find($classId);

        $this->classById[$classId] = $class;

        return $class;
    }

    /**
     * Return the Skill owner's inventory when the Character, inventory, and slots relations are already loaded.
     *
     * @param Skill $skill
     * @return Inventory|null
     */
    private function loadedInventoryWithSlots(Skill $skill): ?Inventory
    {
        if (! $skill->relationLoaded('character') || is_null($skill->character)) {
            return null;
        }

        $character = $skill->character;

        if (! $character->relationLoaded('inventory') || is_null($character->inventory)) {
            return null;
        }

        if (! $character->inventory->relationLoaded('slots')) {
            return null;
        }

        return $character->inventory;
    }

    /**
     * Return the equipped slots from already loaded relations, or null when they must be queried.
     *
     * @param Skill $skill
     * @return Collection|null
     */
    private function equippedSlotsFromLoadedInventory(Skill $skill): ?Collection
    {
        $inventory = $this->loadedInventoryWithSlots($skill);

        if (is_null($inventory)) {
            return null;
        }

        $equippedSlots = $inventory->slots->filter(fn (InventorySlot $slot): bool => $slot->equipped);

        if ($equippedSlots->isEmpty()) {
            return null;
        }

        if (! $equippedSlots->every(fn (InventorySlot $slot): bool => $slot->relationLoaded('item'))) {
            return null;
        }

        return $equippedSlots->values();
    }

    /**
     * Query the equipped inventory slots, falling back to the equipped set's slots when none are equipped.
     *
     * @param Skill $skill
     * @return Collection
     */
    private function queryEquippedSlots(Skill $skill): Collection
    {
        $inventoryId = $this->inventoryId($skill);

        if (is_null($inventoryId)) {
            return collect();
        }

        $slots = InventorySlot::query()
            ->where('inventory_id', $inventoryId)
            ->where('equipped', true)
            ->with('item')
            ->get();

        if ($slots->isNotEmpty()) {
            return $slots;
        }

        $equippedSet = InventorySet::query()
            ->where('character_id', $skill->character_id)
            ->where('is_equipped', true)
            ->with('slots.item')
            ->first();

        if (is_null($equippedSet)) {
            return $slots;
        }

        return $equippedSet->slots;
    }

    /**
     * Return the quest slots naming the Skill from already loaded relations, or null when they must be queried.
     *
     * @param Skill $skill
     * @param string $skillName
     * @return Collection|null
     */
    private function questSlotsFromLoadedInventory(Skill $skill, string $skillName): ?Collection
    {
        $inventory = $this->loadedInventoryWithSlots($skill);

        if (is_null($inventory)) {
            return null;
        }

        if (! $inventory->slots->every(fn (InventorySlot $slot): bool => $slot->relationLoaded('item'))) {
            return null;
        }

        return $inventory->slots->filter(
            fn (InventorySlot $slot): bool => $this->isQuestSlotForSkill($slot, $skillName)
        )->values();
    }

    /**
     * Determine whether the slot holds a quest item naming the Skill.
     *
     * @param InventorySlot $slot
     * @param string $skillName
     * @return bool
     */
    private function isQuestSlotForSkill(InventorySlot $slot, string $skillName): bool
    {
        if (is_null($slot->item)) {
            return false;
        }

        return $slot->item->type === 'quest' && $slot->item->skill_name === $skillName;
    }

    /**
     * Query the inventory quest slots whose items name the Skill.
     *
     * @param int $inventoryId
     * @param string $skillName
     * @return Collection
     */
    private function queryQuestSlots(int $inventoryId, string $skillName): Collection
    {
        return InventorySlot::query()
            ->where('inventory_id', $inventoryId)
            ->whereHas('item', function ($query) use ($skillName) {
                $query->where('type', 'quest')
                    ->where('skill_name', $skillName);
            })
            ->with('item')
            ->get();
    }

    /**
     * Return the active boons from already loaded relations, or null when they must be queried.
     *
     * @param Character $character
     * @return Collection|null
     */
    private function activeBoonsFromLoadedRelations(Character $character): ?Collection
    {
        if (! $character->relationLoaded('boons')) {
            return null;
        }

        $boons = $character->boons->filter(
            fn (CharacterBoon $boon): bool => $boon->complete->greaterThan(now())
        )->values();

        if (! $boons->every(fn (CharacterBoon $boon): bool => $boon->relationLoaded('itemUsed'))) {
            return null;
        }

        return $boons;
    }

    /**
     * Return the Character's Game Class when the relation is already loaded.
     *
     * @param Character $character
     * @return GameClass|null
     */
    private function loadedGameClass(Character $character): ?GameClass
    {
        if (! $character->relationLoaded('class')) {
            return null;
        }

        return $character->class;
    }

    /**
     * Return the inventory id of the Character owning the Skill.
     *
     * @param Skill $skill
     * @return int|null
     */
    private function inventoryId(Skill $skill): ?int
    {
        $characterId = $skill->character_id;

        if (array_key_exists($characterId, $this->inventoryIdByCharacterId)) {
            return $this->inventoryIdByCharacterId[$characterId];
        }

        $this->inventoryIdByCharacterId[$characterId] = $this->loadedInventoryId($skill) ?? Inventory::query()
            ->where('character_id', $characterId)
            ->value('id');

        return $this->inventoryIdByCharacterId[$characterId];
    }

    /**
     * Return the inventory id when the Character and inventory relations are already loaded.
     *
     * @param Skill $skill
     * @return int|null
     */
    private function loadedInventoryId(Skill $skill): ?int
    {
        if (! $skill->relationLoaded('character') || is_null($skill->character)) {
            return null;
        }

        $character = $skill->character;

        if (! $character->relationLoaded('inventory') || is_null($character->inventory)) {
            return null;
        }

        return $character->inventory->id;
    }
}
