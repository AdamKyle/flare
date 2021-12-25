<?php

namespace App\Flare\Values;

class MapNameValue {

    /**
     * @var string $value
     */
    private $value;

    const SURFACE                = 'Surface';
    const LABYRINTH              = 'Labyrinth';
    const DUNGEONS               = 'Dungeons';
    const SHADOW_PLANE           = 'Shadow Plane';
    const HELL                   = 'Hell';

    protected static $values = [
        'Surface'      => self::SURFACE,
        'Labyrinth'    => self::LABYRINTH,
        'Dungeons'     => self::DUNGEONS,
        'Shadow Plane' => self::SHADOW_PLANE,
        'Hell'         => self::HELL,
    ];

    /**
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function isSurface() : bool {
        return $this->value === self::SURFACE;
    }

    public function isLabyrinth() : bool {
        return $this->value === self::LABYRINTH;
    }

    public function isDungeons() : bool {
        return $this->value === self::DUNGEONS;
    }

    public function isShadowPlane() : bool {
        return $this->value === self::SHADOW_PLANE;
    }

    public function isHell() : bool {
        return $this->value === self::HELL;
    }
}
