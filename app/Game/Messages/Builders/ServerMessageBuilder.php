<?php

namespace App\Game\Messages\Builders;

use App\Game\Messages\Types\Concerns\BaseMessageType;
use App\Game\Messages\Types\MessageType;

class ServerMessageBuilder
{
    /**
     * Build message with additional info based on type.
     *
     * - forMessage can be treated as a single value or as a you gained x amount
     * - newValue is treated as a "you now have x amount of y" and should accompany a forValue
     *
     * @param BaseMessageType $type
     * @param string|integer|null|null $forMessage
     * @param string|integer|null|null $newValue
     * @return string
     */
    public function buildWithAdditionalInformation(BaseMessageType $type, string|int|null $forMessage = null, string|int|null $newValue = null): string
    {
        return match ($type->getValue()) {
            'level_up' => 'You are now level: ' . $forMessage . '!',
            'gold' => 'You gained: ' . $forMessage . ' Gold! Your new total amount is: ' . $newValue . '.',
            'gold_dust' => 'You gained: ' . $forMessage . ' Gold Dust! Your new total is: ' . $newValue . '.',
            'shards' => 'You gained: ' . $forMessage . ' Shards! Your new total is: ' . $newValue . '.',
            'copper_coins' => 'You gained: ' . $forMessage . ' Copper Coins! Your new total is: ' . $newValue . '.',
            'gold_rush' => 'Gold Rush! Your gold is now: ' . $forMessage . ' Gold! 5% of your total gold has been awarded to you.',
            'crafted' => 'You crafted a: ' . $forMessage . '!',
            'new_damage_stat' => 'The Creator has changed your classes damage stat to: ' . $forMessage . '. Please adjust your gear accordingly for maximum damage.',
            'disenchanted' => 'Disenchanted the item and got: ' . $forMessage . ' Gold Dust.',
            'lotto_max' => 'You won the daily Gold Dust Lottery! Congrats! You won: ' . $forMessage . ' Gold Dust',
            'daily_lottery' => 'You got: ' . $forMessage . ' Gold Dust from the daily lottery',
            'transmuted' => 'You transmuted a new: ' . $forMessage . ' It shines with a powerful glow!',
            'crafted_gem' => 'You buff, polish, cut, inspect and are finally proud to call this gem your own! You created a: ' . $forMessage,
            'enchantment_failed', 'silenced', 'deleted_affix', 'building_repair_finished', 'building_upgrade_finished',
            'sold_item_on_market', 'new_building', 'kingdom_resources_update', 'unit_recruitment_finished',
            'plane_transfer', 'enchanted', 'moved_location', 'seer_actions' => $forMessage,
            default => $this->build($type),
        };
    }

    /**
     * Build strickly based on type.
     *
     * @param BaseMessageType $type
     * @return string
     */
    public function build(BaseMessageType $type): string
    {
        switch ($type) {
            case 'message_length_0':
                return 'Your message cannot be empty.';
            case 'message_to_max':
                return 'Your message is too long.';
            case 'invalid_command':
                return 'Command not recognized.';
            case 'no_matching_user':
                return 'Could not find a user with that name to private message.';
            case 'no_monster':
                return 'No monster selected. Please select one.';
            case 'dead_character':
                return 'You are dead. Please revive yourself by clicking revive.';
            case 'inventory_full':
                return 'Your inventory is full, you cannot pick up this item!';
            case 'cant_attack':
                return 'Please wait for the timer (beside Again!) to state: Ready!';
            case 'cant_move':
                return 'Please wait for the timer (beside movement options) to state: Ready!';
            case 'cannot_enter_location':
                return 'You are too busy to enter this location. (Are you auto battling? If so, stop. Then enter - then begin again)';
            case 'cannot_move_right':
            case 'cannot_move_down':
            case 'cannot_move_left':
            case 'cannot_move_up':
                return 'You cannot go that way.';
            case 'cannot_walk_on_water':
                return 'You cannot move that way, you are missing the appropriate quest item.';
            case 'not_enough_gold':
                return 'You don\'t have enough Gold for that.';
            case 'not_enough_gold_dust':
                return 'You don\'t have enough Gold Dust for that.';
            case 'not_enough_shards':
                return 'You don\'t have enough Shards for that.';
            case 'cant_enchant':
            case 'cant_craft':
                return 'You must wait for the timer (beside Craft/Enchant) to state: Ready!';
            case 'cant_use_smithy_bench':
                return 'No, child! You are busy. Wait for the timer to finish.';
            case 'to_hard_to_craft':
                return 'You are too low level and thus, you lost your investment and epically failed to craft this item!';
            case 'to_easy_to_craft':
                return 'This is far too easy to craft! You will get no experience for this item.';
            case 'int_to_low_enchanting':
                return 'This enchantment requires you to have more knowledge to attempt. Such wasted efforts! (Requires higher int).';
            case 'something_went_wrong':
                return 'A component was unable to render. Please try refreshing the page.';
            case 'attacking_to_much':
                return 'You are attacking too much in a one minute window.';
            case 'chatting_to_much':
                return 'You can only chat so much in a one minute window. Slow down!';
            case 'message_length_max':
                return 'Your message is far too long.';
            case 'no_matching_command':
                return 'The NPC does not understand you. Their eyes blink in confusion.';
            case 'gold_capped':
                return 'You are gold capped! Max gold a character can hold is two trillion. If you have kingdoms try depositing some of it or buying gold bars or maybe spend some of it?';
            case 'failed_to_craft':
                return 'You failed to craft the item! You lost the investment.';
            case 'failed_to_disenchant':
                return 'Failed to disenchant the item, it shatters before you into ashes. You only got 1 Gold Dust for your efforts.';
            case 'failed_to_transmute':
                return 'You failed to transmute the item. It melts into a pool of liquid gold dust before evaporating away. Wasted efforts!';
            default:
                return '';
        }
    }
}
