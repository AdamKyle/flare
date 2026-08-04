<?php

namespace App\Game\Character\Values;

enum NameTag: string
{
    case ICE_QUEEN_SLAYER = 'ice-queen-slayer';
    case EXPLORER = 'explorer';
    case RULER = 'ruler';
    case DEMON_SLAYER = 'demon-slayer';
    case QUEEN_OF_HEARTS = 'queen-of-hearts';
    case GAMBLING_ADDICT = 'gambling-addict';
    case EARTH_EATER = 'earth-eater';
    case DERANGED_LUNITIC_OF_HELL = 'deranged-lunitic-of-hell';
    case HELPER_OF_MR_WHISKERS = 'helper-of-mr-whiskers';
    case ALL_YOUR_BASES_BELONG_TO_US = 'all-your-bases-belong-to-us';
    case FEARSOME_MAGI_OF_THE_MEMORY = 'fearsome-magi-of-the-memory';

    public function label(): string
    {
        return match ($this) {
            self::ICE_QUEEN_SLAYER => 'Slayer of the Queen of Ice',
            self::EXPLORER => 'Explorer of Tlessa',
            self::RULER => 'Ruler of Tlessa',
            self::DEMON_SLAYER => 'Twisted Demon Slayer of Galidoth',
            self::QUEEN_OF_HEARTS => 'Lover to the Queen of Hearts',
            self::GAMBLING_ADDICT => 'Gambling Addict',
            self::EARTH_EATER => 'Savage Earth Eater',
            self::DERANGED_LUNITIC_OF_HELL => 'A Deranged Lunitic From Hell',
            self::HELPER_OF_MR_WHISKERS => 'Special helper to Mr. Whiskers',
            self::ALL_YOUR_BASES_BELONG_TO_US => 'All your bases belong to us',
            self::FEARSOME_MAGI_OF_THE_MEMORY => 'Most feared magi in all of recent memory',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
