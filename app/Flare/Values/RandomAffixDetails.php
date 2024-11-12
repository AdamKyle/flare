<?php

namespace App\Flare\Values;

use Exception;

class RandomAffixDetails
{
    /**
     * @var string
     */
    private $value;

    const LEGENDARY = 1_000_000_000;

    const MYTHIC = 10_000_000_000;

    const COSMIC = 25_000_000_000;

    /**
     * @var int[]
     */
    protected static $values = [
        3 => self::LEGENDARY,
        4 => self::MYTHIC,
        5 => self::COSMIC,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function getPercentageRange(): array
    {
        switch ($this->value) {
            case self::MYTHIC:
                return [55, 80];
            case self::COSMIC:
                return [95, 110];
            case self::LEGENDARY:
            default:
                return [10, 50];
        }
    }

    public function getDamageRange(): array
    {
        switch ($this->value) {
            case self::MYTHIC:
                return [30, 60];
            case self::COSMIC:
                return [75, 110];
            case self::LEGENDARY:
            default:
                return [8, 25];
        }
    }

    public static function legendaryNames(): array
    {
        return [
            'Petrifying Hatred of Disease',
            'Almighty Rage of the Dead',
            'Sanctifying Earth Tendrils',
            'Serenity of Life',
            'Rebirth of the Ancients',
            'Enchantment of Apathy',
            'Decay and Festering',
            'Scream of Sanctification',
            'Invincibility Rod',
            'Spiritbound Rage',
            'Exile\'s Enchantment',
            'Demonic Infinity',
            'Guardian\'s Fear',
            'Esoteric Flash',
            'Tranquility Gift',
            'Putrefaction of Mortality',
            'Beam of Lightning',
            'Burst of Precision',
            'Gift of Subtlety',
            'Pledge of Mysteries',
            'Pledge of Demolition'
        ];
    }

    public static function mythicalNames(): array
    {
        return [
            'Charge of Disgust',
            'Eternal Crux of Undoing',
            'Boon of Mythic Adventure',
            'Exceptional Curse of Torture',
            'Major Aspect of Conjuring',
            'Miracle of Cat Eyes',
            'Brand of Faultless Dread',
            'Mark of Fire',
            'Oath of Superior Danger',
            'Spark of Mythic Caring',
            'Spark of Elite Magic',
            'Aspect of Prime Visibility',
            'Lesser Core of Sanity',
            'Oath of Explosions',
            'Fortified Boon of Fury',
            'Attunement of Parry',
            'Bond of Speed',
            'Crest of the Full Moon',
            'Mantra of Greater Anxiety',
            'Grace of Absorption',
            'Elite Chant of Grace'
        ];
    }

    public static function cosmicNames(): array
    {
        return [
            'Astral Veil of Eternity',
            'Celestian\'s Starlight Bond',
            'Nebula\'s Whispering Charm',
            'Galactic Aura of Warding',
            'Twilight Horizon Seal',
            'Comet\'s Divine Embrace',
            'Voidheart Illumination',
            'Luminous Rift Chant',
            'Eclipsebound Ward',
            'Astrarium\'s Ethereal Oath',
            'Celestial Flux Mantle',
            'Starfall Requiem',
            'Infinity\'s Halo of Serenity',
            'Moonwoven Blessing',
            'Empyrean\'s Echo Shield',
            'Cosmos Enigma Relic',
            'Radiant Aurora Shroud',
            'Solarbind Sigil',
            'Mystic Aether Pulse',
            'Heaven\'s Fragment Incantation'
        ];
    }
}
