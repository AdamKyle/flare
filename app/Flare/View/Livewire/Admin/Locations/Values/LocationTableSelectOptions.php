<?php

namespace App\Flare\View\Livewire\Admin\Locations\Values;

enum LocationTableSelectOptions: string
{
    case PLEASE_SELECT = '';
    case REGULAR_LOCATIONS = 'regular-locations';
    case WEEKLY_FIGHT_LOCATIONS = 'weekly-fit-locations';

    public static function getLabels(): array
    {
        return [
            self::PLEASE_SELECT->value => 'Please-select',
            self::REGULAR_LOCATIONS->value => 'Regular Locations',
            self::WEEKLY_FIGHT_LOCATIONS->value => 'Weekly Fit Locations',
        ];
    }
}
