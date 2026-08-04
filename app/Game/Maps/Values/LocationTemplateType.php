<?php

namespace App\Game\Maps\Values;

enum LocationTemplateType: string
{
    case PORT = 'port';
    case REGULAR = 'regular';
    case DELVE = 'delve';
    case SPECIAL = 'special';

    public function label(): string
    {
        return match ($this) {
            self::PORT => 'Port',
            self::REGULAR => 'Regular',
            self::DELVE => 'Delve',
            self::SPECIAL => 'Special',
        };
    }

    public static function getNamedValues(): array
    {
        return [
            self::PORT->value => self::PORT->label(),
            self::REGULAR->value => self::REGULAR->label(),
            self::DELVE->value => self::DELVE->label(),
            self::SPECIAL->value => self::SPECIAL->label(),
        ];
    }
}
