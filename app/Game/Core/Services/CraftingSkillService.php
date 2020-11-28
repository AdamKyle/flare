<?php

namespace App\Game\Core\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use Illuminate\Database\Eloquent\Collection;

class CraftingSkillService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Item $item
     */
    private $item;

    /**
     * Set the character
     * 
     * @param Character $character
     * @return CraftingSkillService
     */
    public function setCharacter(Character $character) : CraftingSkillService {
        $this->character = $character;

        return $this;
    }

    /**
     * Get the current crafting skill
     * 
     * @param string $type
     * @return mixed
     */
    public function getCurrentSkill(string $type) {
        return $this->character->skills->filter(function($skill) use($type) {
            return $skill->name === $type . ' Crafting';
        })->first();
    }

    /**
     * Fetch the DC check.
     * 
     * @param Skill $skill
     * @return int
     */
    public function fetchDCCheck(Skill $skill): int {
        $dcCheck = rand(0, $skill->max_level);
        
        return $dcCheck !== 0 ? $dcCheck - $skill->level : 1;
    }

    /**
     * Fetch the characters roll
     * 
     * @param Skill $skill
     * @return mixed
     */
    public function fetchCharacterRoll(Skill $skill) {
        return rand(1, $skill->max_level) * (1 + ($skill->skill_bonus));
    }

    /**
     * Update the characters gold.
     * 
     * Subtract cost from gold.
     * 
     * @param Character $character
     * @param Item $item
     * @return void
     */
    public function updateCharacterGold(Character $character, Item $item): void {
        $character->update([
            'gold' => $character->gold - $item->cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Update the characters gold when enchanting.
     * 
     * Subtract cost from gold.
     * 
     * @param Character $character
     * @param int $cost
     * @return void
     */
    public function updateCharacterGoldForEnchanting(Character $character, int $cost): void {
        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Timeout value.
     * 
     * Possible return values:
     * 
     * - Double is 20 seconds instead of ten. Item has at least one prefix or suffix.
     * - Tripple is 30 seconds instead of ten.
     * - null - item does not have eiher suffix or prefix. Default to 10 seconds.
     * 
     * @param Item $item
     * @param int $affixId
     * @return mixed string | null
     */
    public function timeForEnchanting(Item $item, int $affixId, int $affixLength) {
        $affix = ItemAffix::find($affixId);
        
        if ($affixLength === 2 && !is_null($item->{'item_'.$affix->type.'_id'}) && !is_null($item->{'item_'.$affix->getOppisiteType().'_id'})) {
            return 'tripple';
        }
        
        if (!is_null($item->{'item_'.$affix->type.'_id'})) {
            return 'double';
        }

        return null;
    }

    /**
     * Send off the right server message.
     * 
     * - Server message for too hard, as in the character skill level is too low
     * - Server message for too easy, as in the character skill level is too high, but you still enchant the item.
     * - Server message for gaining enchanting the item.
     * 
     * @param Skill $currentSkill
     * @param InventorySlot $slot
     * @param ItemAffix $itemAffix
     * @param Character $character
     * @return void
     */
    public function sendOffEnchantingServerMessage(Skill $enchantingSkill, InventorySlot $slot, Collection $affixes, Character $character): void {
        forEach($affixes as $affix) {
            $item = $slot->refresh()->item;

            if ($enchantingSkill->level < $affix->skill_level_required) {
                event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));
            } else if ($enchantingSkill->level >= $affix->skill_level_trivial) { 
                event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));
                
                $this->enchantItem($slot, $item, $affix);
    
                $message = 'Applied enchantment: '.$affix->name.' to: ' . $item->name; 
    
                event(new ServerMessageEvent($character->user, 'enchanted', $message));
            } else {
                $dcCheck       = $this->fetchDCCheck($enchantingSkill);
                $characterRoll = $this->fetchCharacterRoll($enchantingSkill);

                if (!is_null($item->{'item_' . $affix->type . '_id'})) {
                    $dcCheck += 10;
                }
    
                if ($characterRoll > $dcCheck) {
                    $this->enchantItem($slot, $item, $affix);
    
                    $message = 'Applied enchantment: '.$affix->name.' to: ' . $item->affix_name; 
    
                    event(new ServerMessageEvent($character->user, 'enchanted', $message));
    
                    event(new UpdateSkillEvent($enchantingSkill));
                } else {
    
                    $slot->delete();

                    if (!is_null($this->item)) {
                        $this->item->delete();
                    }
                    
                    $message = 'You failed to apply enchantments to: ' . $item->name . '. The item shatters before you. You lost the investment.';
    
                    event(new ServerMessageEvent($character->user, 'enchantment_failed', $message));
                }
            }
        }
    }

    /**
     * Send off the right server message.
     * 
     * - Server message for too hard, as in the character skill level is too low
     * - Server message for too easy, as in the character skill level is too high, but you still get the item.
     * - Server message for gaining the item.
     * 
     * @param Skill $currentSkill
     * @param Item $item
     * @param Character $character
     * @return void
     */
    public function sendOffServerMessage(Skill $currentSkill, Item $item, Character $character): void {
        if ($currentSkill->level < $item->skill_level_required) {
            event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));
        } else if ($currentSkill->level >= $item->skill_level_trivial) { 
            event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));

            $this->attemptToPickUpItem($character->refresh(), $item);
        } else {
            $dcCheck       = $this->fetchDCCheck($currentSkill);
            $characterRoll = $this->fetchCharacterRoll($currentSkill);

            if ($characterRoll > $dcCheck) {
                $this->attemptToPickUpItem($character->refresh(), $item);

                event(new UpdateSkillEvent($currentSkill));
            } else {
                event(new ServerMessageEvent($character->user, 'failed_to_craft'));
            }
        }
    }

    protected function attemptToPickUpItem(Character $character, Item $item) {
        if ($character->inventory->slots->count() !== $character->inventory_max) {

            $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new ServerMessageEvent($character->user, 'crafted', $item->name));
        } else {
            event(new ServerMessageEvent($character->user, 'inventory_full'));
        }
    }

    protected function enchantItem(InventorySlot $slot, Item $item, $affix) {

        if (!is_null($this->item)) {
            $this->item->{'item_' . $affix->type . '_id'} = $affix->id;

            $this->item->save();

            return;
        }

        $clonedItem = $item->duplicate();
        
        $clonedItem->{'item_' . $affix->type . '_id'} = $affix->id;
        $clonedItem->market_sellable = true;

        $clonedItem->save();

        $this->item = $clonedItem;

        $slot->update([
            'item_id' => $clonedItem->id,
        ]);
    }
}