<?php

namespace App\Flare\Services;

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
    private ?Skill $skill = null;

    private array $inventoryIdByCharacterId = [];

    private array $equippedSlotsByCharacterId = [];

    private array $questSlotsByInventoryIdAndSkillName = [];

    private array $boonsByCharacterId = [];

    private array $classById = [];

    public function setSkillInstance(Skill $skill): void
    {
        $this->skill = $skill;
    }

    public function getEquippedSlotsWithItems(): Collection
    {
        $characterId = $this->skill->character_id;

        if (array_key_exists($characterId, $this->equippedSlotsByCharacterId)) {
            return $this->equippedSlotsByCharacterId[$characterId];
        }

        $slotsFromLoadedRelations = $this->getEquippedSlotsFromLoadedInventory();

        if (! is_null($slotsFromLoadedRelations)) {
            $this->equippedSlotsByCharacterId[$characterId] = $slotsFromLoadedRelations;

            return $slotsFromLoadedRelations;
        }

        $inventoryId = $this->getInventoryId();

        if (is_null($inventoryId)) {
            $this->equippedSlotsByCharacterId[$characterId] = collect();

            return $this->equippedSlotsByCharacterId[$characterId];
        }

        $slots = InventorySlot::query()
            ->where('inventory_id', $inventoryId)
            ->where('equipped', true)
            ->with('item')
            ->get();

        if ($slots->isEmpty()) {
            $equippedSet = InventorySet::query()
                ->where('character_id', $characterId)
                ->where('is_equipped', true)
                ->with('slots.item')
                ->first();

            if (! is_null($equippedSet)) {
                $slots = $equippedSet->slots;
            }
        }

        $this->equippedSlotsByCharacterId[$characterId] = $slots;

        return $slots;
    }

    public function getQuestSlotsWithItems(): Collection
    {
        $inventoryId = $this->getInventoryId();

        if (is_null($inventoryId)) {
            return collect();
        }

        $skillName = $this->skill->baseSkill->name;
        $questSlotsKey = $inventoryId . '|' . $skillName;

        if (array_key_exists($questSlotsKey, $this->questSlotsByInventoryIdAndSkillName)) {
            return $this->questSlotsByInventoryIdAndSkillName[$questSlotsKey];
        }

        $slotsFromLoadedRelations = $this->getQuestSlotsFromLoadedInventory($skillName);

        if (! is_null($slotsFromLoadedRelations)) {
            $this->questSlotsByInventoryIdAndSkillName[$questSlotsKey] = $slotsFromLoadedRelations;

            return $slotsFromLoadedRelations;
        }

        $slots = InventorySlot::query()
            ->where('inventory_id', $inventoryId)
            ->whereHas('item', function ($query) use ($skillName) {
                $query->where('type', 'quest')
                    ->where('skill_name', $skillName);
            })
            ->with('item')
            ->get();

        $this->questSlotsByInventoryIdAndSkillName[$questSlotsKey] = $slots;

        return $slots;
    }

    public function getBoonsWithItemUsed(): Collection
    {
        $character = $this->skill->character;

        if (is_null($character)) {
            return collect();
        }

        $characterId = $character->id;

        if (array_key_exists($characterId, $this->boonsByCharacterId)) {
            return $this->boonsByCharacterId[$characterId];
        }

        if ($character->relationLoaded('boons')) {
            $boons = $character->boons;

            $allItemUsedLoaded = $boons->every(function ($boon) {
                return $boon->relationLoaded('itemUsed');
            });

            if ($allItemUsedLoaded) {
                $this->boonsByCharacterId[$characterId] = $boons;

                return $boons;
            }
        }

        $boons = CharacterBoon::query()
            ->where('character_id', $characterId)
            ->with('itemUsed')
            ->get();

        $this->boonsByCharacterId[$characterId] = $boons;

        return $boons;
    }

    public function getGameClass(Character $character): ?GameClass
    {
        $classId = $character->game_class_id;

        if (array_key_exists($classId, $this->classById)) {
            return $this->classById[$classId];
        }

        if ($character->relationLoaded('class') && ! is_null($character->class)) {
            $this->classById[$classId] = $character->class;

            return $character->class;
        }

        $class = GameClass::query()->find($classId);

        $this->classById[$classId] = $class;

        return $class;
    }

    private function getEquippedSlotsFromLoadedInventory(): ?Collection
    {
        if (! $this->skill->relationLoaded('character') || is_null($this->skill->character)) {
            return null;
        }

        $character = $this->skill->character;

        if (! $character->relationLoaded('inventory') || is_null($character->inventory)) {
            return null;
        }

        $inventory = $character->inventory;

        if (! $inventory->relationLoaded('slots')) {
            return null;
        }

        $slots = $inventory->slots;

        $equippedSlots = $slots->filter(function ($slot) {
            return $slot->equipped;
        });

        if ($equippedSlots->isEmpty()) {
            return null;
        }

        $allItemsLoaded = $equippedSlots->every(function ($slot) {
            return $slot->relationLoaded('item');
        });

        if (! $allItemsLoaded) {
            return null;
        }

        return $equippedSlots->values();
    }

    private function getQuestSlotsFromLoadedInventory(string $skillName): ?Collection
    {
        if (! $this->skill->relationLoaded('character') || is_null($this->skill->character)) {
            return null;
        }

        $character = $this->skill->character;

        if (! $character->relationLoaded('inventory') || is_null($character->inventory)) {
            return null;
        }

        $inventory = $character->inventory;

        if (! $inventory->relationLoaded('slots')) {
            return null;
        }

        $slots = $inventory->slots;

        $allItemsLoaded = $slots->every(function ($slot) {
            return $slot->relationLoaded('item');
        });

        if (! $allItemsLoaded) {
            return null;
        }

        $questSlots = $slots->filter(function ($slot) use ($skillName) {
            if (is_null($slot->item)) {
                return false;
            }

            return $slot->item->type === 'quest' && $slot->item->skill_name === $skillName;
        });

        return $questSlots->values();
    }

    private function getInventoryId()
    {
        $characterId = $this->skill->character_id;

        if (array_key_exists($characterId, $this->inventoryIdByCharacterId)) {
            return $this->inventoryIdByCharacterId[$characterId];
        }

        if ($this->skill->relationLoaded('character') && ! is_null($this->skill->character)) {
            $character = $this->skill->character;

            if ($character->relationLoaded('inventory') && ! is_null($character->inventory)) {
                $this->inventoryIdByCharacterId[$characterId] = $character->inventory->id;

                return $this->inventoryIdByCharacterId[$characterId];
            }
        }

        $this->inventoryIdByCharacterId[$characterId] = Inventory::query()
            ->where('character_id', $characterId)
            ->value('id');

        return $this->inventoryIdByCharacterId[$characterId];
    }
}
