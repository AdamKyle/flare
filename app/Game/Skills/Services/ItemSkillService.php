<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\Models\ItemSkillProgression;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Game\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use Exception;

class ItemSkillService {

    use ResponseBuilder, FetchEquipped;

    /**
     * Set the skill to being trained.
     *
     * @param Character $character
     * @param integer $itemId
     * @param integer $itemSkillProgressionId
     * @return array
     * @throws Exception
     */
    public function trainSkill(Character $character, int $itemId, int $itemSkillProgressionId): array {

        $foundItem = $this->fetchItemWithSkill($character, $itemId);

        if (is_null($foundItem)) {
            return $this->errorResult('No item found. Either it is not equipped, or it does not exist.');
        }

        $foundSkill = $this->fetchItemSkillProgression($foundItem, $itemSkillProgressionId);

        if (is_null($foundSkill)) {
            return $this->errorResult('No skill found on said item.');
        }

        if (!$this->canTrainSkill($foundSkill)) {
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
            'message' => 'You are now training: ' . $foundSkill->itemSkill->name
        ]);
    }

    /**
     * Stop training the skill
     *
     * @param Character $character
     * @param integer $itemId
     * @param integer $itemSkillProgressionId
     * @return array
     */
    public function stopTrainingSkill(Character $character, int $itemId, int $itemSkillProgressionId): array {
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
            'message' => 'You stopped training: ' . $foundSkill->itemSkill->name
        ]);
    }

    /**
     * Can we train the skill?
     *
     * - Check to make sure the parent skill is trained if needed.
     *
     * @param ItemSkillProgression $itemSkillProgression
     * @return bool
     */
    protected function canTrainSkill(ItemSkillProgression $itemSkillProgression): bool {
        $itemSkill = $itemSkillProgression->itemSkill;

        $parentSkill = $itemSkill->parent;

        if (is_null($parentSkill)) {
            return true;
        }

        $parentSkillProgression = ItemSkillProgression::where('item_skill_id', $parentSkill->id)->first();

        return $parentSkillProgression->current_level >= $itemSkill->parent_level_needed;
    }

    /**
     * Fetch the item with the skill from the equipped inventory
     *
     * @param Character $character
     * @param integer $itemId
     * @return Item|null
     */
    protected function fetchItemWithSkill(Character $character, int $itemId): ?Item {
        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return null;
        }

        $slot = $equippedItems->where('item.type', 'artifact')->where('item.id', $itemId)->first();

        if (is_null($slot)) {
            return null;
        }

        return $slot->item;
    }

    /**
     * fetch the skill progression record from the item
     *
     * @param Item $item
     * @param [type] $itemSkillProgressionId
     * @return ItemSkillProgression|null
     */
    protected function fetchItemSkillProgression(Item $item, $itemSkillProgressionId): ?ItemSkillProgression {

        if ($item->itemSkillProgressions->isEmpty()) {
            return null;
        }

        return $item->itemSkillProgressions->where('item_skill_id', $itemSkillProgressionId)->first();
    }

    /**
     * Stop training all skills.
     *
     * @param Item $item
     * @return void
     */
    protected function stopTrainingOtherSkills(Item $item): void {
        $item->itemSkillProgressions()->update(['is_training' => false]);
    }
}
