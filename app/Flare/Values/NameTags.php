<?php

namespace App\Flare\Values;

use Exception;

class NameTags
{
    const ICE_QUEEN_SLAYER = 'ice-queen-slayer';

    const EXPLORER = 'explorer';

    const RULER = 'ruler';

    const DEMON_SLAYER = 'demon-slayer';

    const QUEEN_OF_HEARTS = 'queen-of-hearts';

    const GAMBLING_ADDICT = 'gambling-addict';

    const EARTH_EATER = 'earth-eater';

    const DERANGED_LUNITIC_OF_HELL = 'deranged-lunitic-of-hell';

    const HELPER_OF_MR_WHISKERS = 'helper-of-mr-whiskers';

    const ALL_YOUR_BASES_BELONG_TO_US = 'all-your-bases-belong-to-us';

    const FEARSOME_MAGI_OF_THE_MEMORY = 'fearsome-magi-of-the-memory';


    /**
     * @var string[]
     */
    protected static array $values = [
        self::ICE_QUEEN_SLAYER => self::ICE_QUEEN_SLAYER,
        self::EXPLORER => self::EXPLORER,
        self::RULER => self::RULER,
        self::DEMON_SLAYER => self::DEMON_SLAYER,
        self::QUEEN_OF_HEARTS => self::QUEEN_OF_HEARTS,
        self::GAMBLING_ADDICT => self::GAMBLING_ADDICT,
        self::EARTH_EATER => self::EARTH_EATER,
        self::DERANGED_LUNITIC_OF_HELL => self::DERANGED_LUNITIC_OF_HELL,
        self::HELPER_OF_MR_WHISKERS => self::HELPER_OF_MR_WHISKERS,
        self::ALL_YOUR_BASES_BELONG_TO_US => self::ALL_YOUR_BASES_BELONG_TO_US,
        self::FEARSOME_MAGI_OF_THE_MEMORY => self::FEARSOME_MAGI_OF_THE_MEMORY,
    ];

    public static array $valueNames = [
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
        self::FEARSOME_MAGI_OF_THE_MEMORY => 'The most feared magi in all of recent memory',
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }
    }
}
