<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughTarget: string
{
    case MANUAL_FIGHT_INITIATE = 'manual-fight-initiate';
    case MANUAL_FIGHT_ACTIONS = 'manual-fight-actions';
    case BATTLE_MESSAGES = 'battle-messages';
    case SERVER_MESSAGE_ITEM = 'server-message-item';
    case ITEM_STATS = 'item-stats';
    case ITEM_EQUIP_ACTION = 'item-equip-action';

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
            self::SERVER_MESSAGE_ITEM => 'Found Item Message',
            self::ITEM_STATS => 'Item Stats',
            self::ITEM_EQUIP_ACTION => 'Equip Item Action',
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
