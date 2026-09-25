<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkillProgression;
use App\Game\Character\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Traits\ResponseBuilder;

class ItemSkillService
{
    use FetchEquipped, ResponseBuilder;

    /**
     * Set the skill to being trained.
     *
     * @param Character $character The character managing the equipped item.
     * @param int $itemId The equipped artifact identifier.
     * @param int $itemSkillProgressionId The progression identifier.
     * @return array The training result.
     */
    public function trainSkill(Character $character, int $itemId, int $itemSkillProgressionId): array
    {
        $foundItem = $this->fetchItemWithSkill($character, $itemId);

        if (is_null($foundItem)) {
            return $this->errorResult('No item found. Either it is not equipped, or it does not exist.');
        }

        $foundSkill = $this->fetchItemSkillProgression($foundItem, $itemSkillProgressionId);

        if (is_null($foundSkill)) {
            return $this->errorResult('No skill found on said item.');
        }

        if ($this->normalizeMaxLevelProgression($foundSkill)) {
            return $this->errorResult('This item skill is already at max level.');
        }

        if (! $this->canTrainSkill($foundSkill)) {
            return $this->errorResult('You must train the parent skill first.');
        }

        $this->stopTrainingOtherSkills($foundItem);

        $foundSkill->update([
            'is_training' => true,
        ]);

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));

        $foundSkill = $foundSkill->refresh();

        return $this->successResult([
            'message' => 'You are now training: '.$foundSkill->itemSkill->name,
        ]);
    }

    /**
     * Stop training the selected item skill.
     *
     * @param Character $character The character managing the equipped item.
     * @param int $itemId The equipped artifact identifier.
     * @param int $itemSkillProgressionId The progression identifier.
     * @return array The stop-training result.
     */
    public function stopTrainingSkill(Character $character, int $itemId, int $itemSkillProgressionId): array
    {
        $foundItem = $this->fetchItemWithSkill($character, $itemId);

        if (is_null($foundItem)) {
            return $this->errorResult('Item must be equipped to manage the training of a skill.');
        }

        $foundSkill = $this->fetchItemSkillProgression($foundItem, $itemSkillProgressionId);

        if (is_null($foundSkill)) {
            return $this->errorResult('No skill found on said item.');
        }

        $foundSkill->update([
            'is_training' => false,
        ]);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));

        return $this->successResult([
            'message' => 'You stopped training: '.$foundSkill->itemSkill->name,
        ]);
    }

    /**
     * Determine whether the item-local parent requirement is satisfied.
     *
     * @param ItemSkillProgression $itemSkillProgression The progression being trained.
     * @return bool Whether training is allowed.
     */
    private function canTrainSkill(ItemSkillProgression $itemSkillProgression): bool
    {
        $itemSkill = $itemSkillProgression->itemSkill;

        $parentSkill = $itemSkill->parent;

        if (is_null($parentSkill)) {
            return true;
        }

        $parentSkillProgression = ItemSkillProgression::where('item_id', $itemSkillProgression->item_id)
            ->where('item_skill_id', $parentSkill->id)
            ->first();

        if (is_null($parentSkillProgression)) {
            return false;
        }

        return $parentSkillProgression->current_level >= $itemSkill->parent_level_needed;
    }

    /**
     * Fetch an equipped artifact owned by the character.
     *
     * @param Character $character The character whose equipment is searched.
     * @param int $itemId The artifact identifier.
     * @return Item|null The equipped artifact when found.
     */
    private function fetchItemWithSkill(Character $character, int $itemId): ?Item
    {
        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return null;
        }

        $slot = $equippedItems->where('item.type', '=', 'artifact')->where('item.id', '=', $itemId)->first();

        if (is_null($slot)) {
            return null;
        }

        return $slot->item;
    }

    /**
     * fetch the skill progression record from the item
     *
     * @param Item $item The item owning the progression.
     * @param int $itemSkillProgressionId The progression identifier.
     * @return ItemSkillProgression|null The matching progression.
     */
    private function fetchItemSkillProgression(Item $item, int $itemSkillProgressionId): ?ItemSkillProgression
    {

        if ($item->itemSkillProgressions->isEmpty()) {
            return null;
        }

        return $item->itemSkillProgressions()->find($itemSkillProgressionId);
    }

    /**
     * Stop training every skill on the item.
     *
     * @param Item $item The item whose progressions are updated.
     * @return void
     */
    private function stopTrainingOtherSkills(Item $item): void
    {
        $item->itemSkillProgressions()->update(['is_training' => false]);
    }

    /**
     * Normalize a max-level progression before training.
     *
     * @param ItemSkillProgression $itemSkillProgression The progression being checked.
     * @return bool Whether the progression is already maxed.
     */
    private function normalizeMaxLevelProgression(ItemSkillProgression $itemSkillProgression): bool
    {
        $maxLevel = $itemSkillProgression->itemSkill->max_level;

        if ($itemSkillProgression->current_level < $maxLevel) {
            return false;
        }

        $itemSkillProgression->update([
            'current_level' => $maxLevel,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        return true;
    }
}
