<?php

namespace App\Game\Skills\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\Traits\UpdateCharacterGold;

class EnchantingService {

    use ResponseBuilder, UpdateCharacterGold;

    /**
     * @var CharacterInformationBuilder $characterInformationBuilder;
     */
    private $characterInformationBuilder;

    /**
     * @var EnchantItemService $enchantItemService
     */
    private $enchantItemService;

    /**
     * @var bool $sentToEasyMessage
     */
    private $sentToEasyMessage = false;

    /**
     * Only set if the affix to be applied was too easy to enchant.
     *
     * @var bool $wasTooEasy
     */
    private $wasTooEasy = false;

    /**
     * Constructor
     *
     * @param CharacterInformationBuilder $characterInformationBuilder
     * @param EnchantItemService $enchantItemService
     * @return void
     */
    public function __construct(CharacterInformationBuilder $characterInformationBuilder, EnchantItemService $enchantItemService) {
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->enchantItemService          = $enchantItemService;
    }

    /**
     * Fetches the affixes for a character.
     *
     * Only returns that which the player has the skill level and intelligence for.
     *
     * @param Character $character
     * @return array
     */
    public function fetchAffixes(Character $character): array {
        $characterInfo   = $this->characterInformationBuilder->setCharacter($character);
        $enchantingSkill = $this->getEnchantingSkill($character);

        return [
            'affixes'             => $this->getAvailableAffixes($characterInfo, $enchantingSkill),
            'character_inventory' => array_values($this->fetchCharacterInventory($character)),
        ];
    }

    /**
     * Enchant an item.
     *
     * Attamepts to enchant an item with the supplied afixes and slot.
     *
     * The params passed in must be the request params coming back from the request.
     *
     * The array returned contains the the status and the details, either a list of
     * the characters inventory and their affixes they can enchant or a error message.
     *
     * eg, ['message' => '', 'status' => 422] or
     * ['affixes' => Collection, 'character_inventory' => [...], 'status' => 200]
     *
     * @param Character $character
     * @param array $params
     */
    public function enchant(Character $character, array $params): array {
        $characterInfo   = $this->characterInformationBuilder->setCharacter($character);
        $enchantingSkill = $this->getEnchantingSkill($character);
        $slot            = $this->getSlotFromInventory($character, $params['slot_id']);

        if (is_null($slot)) {
            return $this->errorResult('No such inventory slot.');
        }

        if ($params['cost'] > $character->gold) {
            return $this->errorResult('Not enough gold.');
        }

        $timeOut = $this->timeForEnchanting($slot->item);

        $this->updateCharacterGold($character, $params['cost']);

        try {
            $this->attachAffixes($params['affix_ids'], $slot, $enchantingSkill, $character, $params['cost']);

            $this->enchantItemService->updateSlot($slot);

            event(new CraftedItemTimeOutEvent($character->refresh(), $timeOut));

            return $this->successResult([
                'affixes'             => $this->getAvailableAffixes($characterInfo, $enchantingSkill),
                'character_inventory' => array_values($this->fetchCharacterInventory($character)),
            ]);
        } catch (Exception $e) {
            // Something went wrong, give their gold back
            $this->giveGoldBack($character->refresh(), $params['cost']);

            return $this->errorResult($e->getMessage());
        }
    }

    protected function getEnchantingSkill(Character $character): Skill {
        return $character->skills()->where('game_skill_id', GameSkill::where('name', 'Enchanting')->first()->id)->first();
    }

    protected function getSlotFromInventory(Character $character, int $slotId) {
        return $character->refresh()->inventory->slots->where('id', $slotId)->where('equipped', false)->first();
    }

    protected function fetchCharacterInventory(Character $character): Array {
        return $character->refresh()->inventory->slots->filter(function($slot) {
            if ($slot->item->type !== 'quest' && $slot->item->type !== 'alchemy' && !$slot->equipped) {
                return $slot->item->load('itemSuffix', 'itemPrefix')->toArray();
            }
        })->all();
    }

    protected function getAvailableAffixes(CharacterInformationBuilder $builder, Skill $enchantingSkill): Collection {
        return ItemAffix::select('name', 'cost', 'id', 'type')
                        ->where('int_required', '<=', $builder->statMod('int'))
                        ->where('skill_level_required', '<=', $enchantingSkill->level)
                        ->orderBy('cost', 'asc')
                        ->get();
    }

    protected function timeForEnchanting(Item $item) {

        if (!is_null($item->itemPrefix) && !is_null($item->itemSuffix)) {
            return 'tripple';
        }

        if (!is_null($item->itemPrefix) || !is_null($item->itemSuffix)) {
            return 'double';
        }

        return null;
    }

    protected function attachAffixes
    (array $affixes, InventorySlot $slot, Skill $enchantingSkill, Character $character) {
        foreach ($affixes as $affixId) {
            $affix = ItemAffix::find($affixId);

            if (is_null($affix)) {
                throw new Exception('Affix not found for: ' . $affixId);
            }

            // Reset.
            $this->wasTooEasy = false;

            if ($enchantingSkill->level < $affix->skill_level_required) {
                event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));

                return;
            }

            if ($enchantingSkill->level >= $affix->skill_level_trivial) {
                if (!$this->sentToEasyMessage) {
                    event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));
                    $this->sentToEasyMessage = true;
                }

                $this->processedEnchant($slot, $affix, $character, $enchantingSkill, true);

                $this->wasTooEasy = true;

            }

            /**
             * If the affix wasn't too easy to atach, attempt to enchant with the difficulty check
             * in place.
             *
             * If we fail to do this then we retrun from the loop.
             */
            if (!$this->wasTooEasy) {
                if (!$this->processedEnchant($slot, $affix, $character, $enchantingSkill)) {
                    return;
                }
            }
        }
    }

    protected function processedEnchant(InventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false) {
        $enchanted = $this->enchantItemService->attachAffix($slot->item, $affix, $enchantingSkill, $tooEasy);

        if ($enchanted) {
            $this->appliedEnchantment($slot, $affix, $character, $enchantingSkill, $tooEasy);
        } else {
            $this->failedToApplyEnchantment($slot, $affix, $character);

            return false;
        }

        return true;
    }

    protected function appliedEnchantment(InventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false) {
        $message = 'Applied enchantment: '.$affix->name.' to: ' . $slot->item->refresh()->affix_name;

        event(new ServerMessageEvent($character->user, 'enchanted', $message));

        if (!$tooEasy) {
            event(new UpdateSkillEvent($enchantingSkill));
        }
    }

    protected function failedToApplyEnchantment(InventorySlot $slot, ItemAffix $affix, Character $character) {
        $message = 'You failed to apply '.$affix->name.' to: ' . $slot->item->refresh()->affix_name . '. The item shatters before you. You lost the investment.';

        event(new ServerMessageEvent($character->user, 'enchantment_failed', $message));

        $this->enchantItemService->deleteSlot($slot);
    }
}
