<?php

namespace App\Flare\View\Livewire\Admin\Locations\Values;

enum LocationTableSelectOptions: string {
    case PLEASE_SELECT = '';
    case INCREASES_ENEMY_STRENGTH = 'increase-enemy-strength';
    case REGULAR_LOCATIONS = 'regular-locations';
    case WEEKLY_FIGHT_LOCATIONS = 'weekly-fit-locations';

    public static function getLabels(): array
    {
        return [
            self::PLEASE_SELECT->value => 'Please-select',
            self::REGULAR_LOCATIONS->value => 'Regular Locations',
            self::INCREASES_ENEMY_STRENGTH->value => 'Increases Enemy Strength',
            self::WEEKLY_FIGHT_LOCATIONS->value => 'Weekly Fit Locations',
        ];
    }
}