<?php

namespace App\Game\Core\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
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
     * Send off the right server message.
     * 
     * - Server message for too hard, as in the character skill level is too low
     * - Server message for too easy, as in the character skill level is too high, but you still enchant the item.
     * - Server message for gaining enchanting the item.
     * 
     * @param Skill $currentSkill
     * @param Item $item
     * @param ItemAffix $itemAffix
     * @param Character $character
     * @return void
     */
    public function sendOffEnchantingServerMessage(Skill $enchantingSkill, Item $item, Collection $affixes, Character $character): void {
        forEach($affixes as $affix) {
            if ($enchantingSkill->level < $affix->skill_level_required) {
                event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));
            } else if ($enchantingSkill->level >= $affix->skill_level_trivial) { 
                event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));
                
                $this->enchantItem($item, $affix);
    
                $message = 'Applied enchantment: '.$affix->name.' to: ' . $item->name; 
    
                event(new ServerMessageEvent($character->user, 'enchanted', $message));
            } else {
                $dcCheck       = $this->fetchDCCheck($enchantingSkill);
                $characterRoll = $this->fetchCharacterRoll($enchantingSkill);

                if (!is_null($item->{'item_' . $affix->type . '_id'})) {
                    $dcCheck += 10;
                }
    
                if ($characterRoll > $dcCheck) {
                    $this->enchantItem($item, $affix);
    
                    $message = 'Applied enchantment: '.$affix->name.' to: ' . $item->name; 
    
                    event(new ServerMessageEvent($character->user, 'enchanted', $message));
    
                    event(new UpdateSkillEvent($enchantingSkill));
                } else {
    
                    $character->inventory->slots->where('item_id', $item->id)->first()->delete();
                    
                    $message = 'You failed to apply enchantments to: ' . $item->name . '. The item shatters before you. You lost the investment.';
    
                    event(new ServerMessageEvent($character->user, 'enchantment_failed', $message));

                    return;
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
            dump($dcCheck, $characterRoll);
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

    protected function enchantItem(Item $item, $affix) {
        $item->{'item_' . $affix->type . '_id'} = $affix->id;

        $item->save();
    }
}