<?php

namespace App\Game\Messages\Builders;

class ServerMessageBuilder {

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
                return 'You are dead. Please revive your self by clicking revive.';
            case 'inventory_full':
                return 'Your inventory is full, you cannot pick up this drop!';
            case 'cant_attack':
                return 'Please wait for the timer (beside Again!) to state: Ready!';
            case 'cant_move':
                return 'Please wait for the timer (beside movement options) to state: Ready!';
            case 'cannot_move_up':
                return 'You cannot go that way.';
            case 'cannot_move_left':
                return 'You cannot go that way.';
            case 'cannot_move_down':
                return 'You cannot go that way.';
            case 'cannot_move_right':
                return 'You cannot go that way.';
            case 'cannot_walk_on_water':
                return 'You cannot walk on water without a Flask of Fresh Air.';
            case 'not_enough_gold':
                return 'You dont have enough gold for that.';
            case 'cant_craft':
                return 'You must wait for the timer (beside Craft) to state: Ready!';
            case 'to_hard_to_craft':
                return 'You lost your investment and epically failed to craft this item.';
            default:
                return '';
        }
    }
}
