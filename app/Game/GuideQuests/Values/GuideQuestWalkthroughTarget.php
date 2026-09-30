<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughTarget: string
{
    case MANUAL_FIGHT_INITIATE = 'manual-fight-initiate';
    case MANUAL_FIGHT_ACTIONS = 'manual-fight-actions';
    case BATTLE_MESSAGES = 'battle-messages';
    case SERVER_MESSAGES_TAB = 'server-messages-tab';
    case SERVER_MESSAGES_PANEL = 'server-messages-panel';
    case SERVER_MESSAGE_QUEST_ITEM = 'server-message-quest-item';
    case SERVER_MESSAGE_ITEM = 'server-message-item';
    case QUEST_ITEM_DETAILS = 'quest-item-details';
    case SIDE_PEEK_CLOSE = 'side-peek-close';
    case ITEM_DETAILS = 'item-details';
    case ITEM_STATS = 'item-stats';
    case ITEM_AFFIXES = 'item-affixes';
    case ITEM_AFFIX_DETAILS = 'item-affix-details';
    case ITEM_AFFIX_CLOSE = 'item-affix-close';
    case ITEM_ACTIONS = 'item-actions';
    case ITEM_EQUIP_ACTION = 'item-equip-action';
    case ITEM_EQUIP_COMPARISON = 'item-equip-comparison';
    case ITEM_EQUIP_OPTIONS = 'item-equip-options';
    case ITEM_EQUIP_CONFIRM = 'item-equip-confirm';
    case ITEM_EQUIPPED_STATE = 'item-equipped-state';

    /**
     * Return the Admin-facing target label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MANUAL_FIGHT_INITIATE => 'Initiate Fight',
            self::MANUAL_FIGHT_ACTIONS => 'Manual Fight Actions',
            self::BATTLE_MESSAGES => 'Battle Messages',
            self::SERVER_MESSAGES_TAB => 'Server Messages Tab',
            self::SERVER_MESSAGES_PANEL => 'Server Messages Panel',
            self::SERVER_MESSAGE_QUEST_ITEM => 'Quest Item Server Message',
            self::SERVER_MESSAGE_ITEM => 'Equipment Item Server Message',
            self::QUEST_ITEM_DETAILS => 'Quest Item Details',
            self::SIDE_PEEK_CLOSE => 'Side Peek Close',
            self::ITEM_DETAILS => 'Item Details',
            self::ITEM_STATS => 'Item Stats',
            self::ITEM_AFFIXES => 'Item Enchantments',
            self::ITEM_AFFIX_DETAILS => 'Item Enchantment Details',
            self::ITEM_AFFIX_CLOSE => 'Close Item Enchantment Details',
            self::ITEM_ACTIONS => 'Item Actions',
            self::ITEM_EQUIP_ACTION => 'Equip Item Action',
            self::ITEM_EQUIP_COMPARISON => 'Equipment Comparison',
            self::ITEM_EQUIP_OPTIONS => 'Equip Item Options',
            self::ITEM_EQUIP_CONFIRM => 'Equip Item Confirmation',
            self::ITEM_EQUIPPED_STATE => 'Equipped Item State',
        };
    }

    /**
     * Return every target as an Admin select option.
     *
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn (self $target): array => [
            'value' => $target->value,
            'label' => $target->label(),
        ])->all();
    }
}
