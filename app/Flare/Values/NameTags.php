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

    private string $value;

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
    ];

    public static array $valueNames = [
        self::ICE_QUEEN_SLAYER => 'Slayer of the Queen of Ice',
        self::EXPLORER => 'Explorer of Tlessa',
        self::RULER => 'Ruler of Tlessa',
        self::DEMON_SLAYER => 'Twisted Demon Slayer of Galidoth',
        self::QUEEN_OF_HEARTS => 'Lover to the Queen of Hearts',
        self::GAMBLING_ADDICT => 'Gambling Addict',
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }
}
