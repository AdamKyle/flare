<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughEvent: string
{
    case FIGHT_STARTED = 'fight-started';
    case COMBAT_ACTION_SUCCEEDED = 'combat-action-succeeded';
    case ITEM_EQUIPPED = 'item-equipped';

    /**
     * Return the Admin-facing event label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::FIGHT_STARTED => 'Fight started',
            self::COMBAT_ACTION_SUCCEEDED => 'Combat action succeeded',
            self::ITEM_EQUIPPED => 'Item equipped',
        };
    }

    /**
     * Return every event as an Admin select option.
     *
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn (self $event): array => [
            'value' => $event->value,
            'label' => $event->label(),
        ])->all();
    }
}
