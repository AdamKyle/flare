<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughEvent: string
{
    case FIGHT_STARTED = 'fight-started';
    case COMBAT_ACTION_SUCCEEDED = 'combat-action-succeeded';
    case ITEM_EQUIPPED = 'item-equipped';
    case EXPLORATION_FIGHT_COMPLETED = 'exploration-fight-completed';
    case SKILL_TRAINING_STARTED = 'skill-training-started';
    case FACTION_ASSISTANCE_STARTED = 'faction-assistance-started';

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
            self::EXPLORATION_FIGHT_COMPLETED => 'Exploration fight completed',
            self::SKILL_TRAINING_STARTED => 'Skill training started',
            self::FACTION_ASSISTANCE_STARTED => 'Faction assistance started',
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
