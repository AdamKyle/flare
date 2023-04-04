<?php

namespace App\Game\Messages\Builders;

use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;

class ServerMessageBuilder {

    /**
     * Build the server message
     *
     * @param string $type
     * @return string
     */
    public function build(string $type): string {
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
                return 'Gold Rush! You are now gold capped!';
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

    /**
     * @param string $type
     * @param string|int|null $forMessage
     * @return string
     */
    public function buildWithAdditionalInformation(string $type, string|int $forMessage = null): string {
        switch($type) {
            case 'level_up':
                return 'You are now level: ' . $forMessage . '!';
            case 'gold_rush':
                return 'Gold Rush! Your gold is now: ' . $forMessage . ' Gold! 5% of your total gold has been awarded to you.';
            case 'crafted':
                return 'You crafted a: ' . $forMessage . '!';
            case 'new_damage_stat':
                return 'The Creator has changed your classes damage stat to: ' . $forMessage . '. Please adjust your gear accordingly for maximum damage.';
            case 'disenchanted':
                return 'Disenchanted the item and got: ' . $forMessage . ' Gold Dust.';
            case 'lotto_max':
                return 'You won the daily Gold Dust Lottery! Congrats! You won: ' . $forMessage . ' Gold Dust';
            case 'daily_lottery':
                return 'You got: ' . $forMessage . ' Gold Dust from the daily lottery';
            case 'transmuted':
                return 'You transmuted a new: ' . $forMessage . ' It shines with a powerful glow!';
            case 'crafted_gem':
                return 'You buff, polish, cut, inspect and are finally proud to call this gem your own! You created a: ' . $forMessage;
            case 'enchantment_failed':
            case 'silenced':
            case 'deleted_affix':
            case 'building_repair_finished':
            case 'building_upgrade_finished':
            case 'sold_item':
            case 'new_building':
            case 'kingdom_resources_update':
            case 'unit_recruitment_finished':
            case 'plane_transfer':
            case 'enchanted':
            case 'moved_location':
            case 'seer_actions':
                return $forMessage;
            default:
                return $this->build($type);

        }
    }
}
