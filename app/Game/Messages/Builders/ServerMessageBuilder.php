<?php

namespace App\Game\Messages\Builders;

use App\Game\Messages\Types\Concerns\BaseMessageType;

class ServerMessageBuilder
{
    /**
     * Build message with additional info based on type.
     *
     * - forMessage can be treated as a single value or as a you gained x amount
     * - newValue is treated as a "you now have x amount of y" and should accompany a forValue
     */
    public function buildWithAdditionalInformation(BaseMessageType $type, string|int|null $forMessage = null, string|int|null $newValue = null): string
    {
        return match ($type->getValue()) {
            'level_up' => 'You are now level: '.$forMessage.'!',
            'gold' => 'You gained: '.$forMessage.' Gold! Your new total amount is: '.$newValue.'.',
            'gold_dust' => 'You gained: '.$forMessage.' Gold Dust! Your new total is: '.$newValue.'.',
            'shards' => 'You gained: '.$forMessage.' Shards! Your new total is: '.$newValue.'.',
            'copper_coins' => 'You gained: '.$forMessage.' Copper Coins! Your new total is: '.$newValue.'.',
            'gold_rush' => 'Gold Rush! Your gold has increased by: '.$forMessage.' Gold! 5% of your total gold has been awarded to you. You now have: '.$newValue.' Gold!',
            'crafted' => 'You crafted a: '.$forMessage.'!',
            'new_damage_stat' => 'The Creator has changed your classes damage stat to: '.$forMessage.'. Please adjust your gear accordingly for maximum damage.',
            'disenchanted' => 'Disenchanted the item and got: '.$forMessage.' Gold Dust.',
            'lotto_max' => 'You won the daily Gold Dust Lottery! Congrats! You won: '.$forMessage.' Gold Dust',
            'daily_lottery' => 'You got: '.$forMessage.' Gold Dust from the daily lottery',
            'transmuted' => 'You transmuted a new: '.$forMessage.' It shines with a powerful glow!',
            'crafted_gem' => 'You buff, polish, cut, inspect and are finally proud to call this gem your own! You created a: '.$forMessage,
            'enchantment_failed', 'silenced', 'deleted_affix', 'building_repair_finished', 'building_upgrade_finished',
            'sold_item_on_market', 'new_building', 'kingdom_resources_update', 'unit_recruitment_finished',
            'plane_transfer', 'enchanted', 'moved_location', 'seer_actions' => $forMessage,
            default => $this->build($type),
        };
    }

    /**
     * Build strickly based on type.
     */
    public function build(BaseMessageType $type): string
    {
        return match ($type->getValue()) {
            'invalid_message_length' => 'Your message cannot be empty.',
            'inventory_is_full' => 'Your inventory is full, you cannot pick up this item!',
            'cant_move' => 'Please wait for the timer (beside movement options) to state: Ready!',
            'cannot_move_right',
            'cannot_move_down',
            'cannot_move_left',
            'cannot_move_up' => 'You cannot go that way.',
            'not_enough_gold' => 'You don\'t have enough Gold for that.',
            'not_enough_gold_dust' => 'You don\'t have enough Gold Dust for that.',
            'not_enough_shards' => 'You don\'t have enough Shards for that.',
            'to_hard_to_craft' => 'You are too low level and thus, you lost your investment and epically failed to craft this item!',
            'to_easy_to_craft' => 'This is far too easy to craft! You will get no experience for this item.',
            'int_to_low_enchanting' => 'This enchantment requires you to have more knowledge to attempt. Such wasted efforts! (Requires higher int).',
            'chatting_to_much' => 'You can only chat so much in a one minute window. Slow down!',
            'gold_capped' => 'You are gold capped! Max gold a character can hold is two trillion. If you have kingdoms try depositing some of it or buying gold bars or maybe spend some of it?',
            'failed_to_craft' => 'You failed to craft the item! You lost the investment.',
            'failed_to_disenchant' => 'Failed to disenchant the item, it shatters before you into ashes. You only got 1 Gold Dust for your efforts.',
            'failed_to_transmute' => 'You failed to transmute the item. It melts into a pool of liquid gold dust before evaporating away. Wasted efforts!',
            default => '',
        };
    }
}
