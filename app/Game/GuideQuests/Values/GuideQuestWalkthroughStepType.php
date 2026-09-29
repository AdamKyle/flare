<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughStepType: string
{
    case CONTINUE = 'continue';
    case CLICK = 'click';
    case EVENT = 'event';

    /**
     * Return the player-authoring label for this step type.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTINUE => 'Continue',
            self::CLICK => 'Click',
            self::EVENT => 'Successful event',
        };
    }

    /**
     * Return every step type as an Admin select option.
     *
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->all();
    }
}
