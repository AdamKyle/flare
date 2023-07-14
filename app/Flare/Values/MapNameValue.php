<?php

namespace App\Flare\Values;

use App\Flare\Models\Location;

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
    const PURGATORY              = 'Purgatory';

    /**
     * @var array $values
     */
    protected static $values = [
        'Surface'      => self::SURFACE,
        'Labyrinth'    => self::LABYRINTH,
        'Dungeons'     => self::DUNGEONS,
        'Shadow Plane' => self::SHADOW_PLANE,
        'Hell'         => self::HELL,
        'Purgatory'    => self::PURGATORY,
    ];

    /**
     * @var array $kingdomColors
     */
    public static $kingdomColors = [
        self::SURFACE   => '#879bc2',
        self::LABYRINTH => '#ff99c4',
        self::DUNGEONS  => '#10eb2e',
        self::HELL      => '#1194d1',
        self::PURGATORY => '#000000',
    ];

    public static $mapModifiers = [
        self::SURFACE => [
            'xp_bonus'                     => 0.0,
            'skill_training_bonus'         => 0.0,
            'drop_chance_bonus'            => 0.0,
            'enemy_stat_bonus'             => 0.0,
            'character_attack_reduction'   => 0.0,
            'required_location_id'         => 0.0,
        ],
        self::LABYRINTH => [
            'xp_bonus'                     => 0.0,
            'skill_training_bonus'         => 0.0,
            'drop_chance_bonus'            => 0.0,
            'enemy_stat_bonus'             => 0.0,
            'character_attack_reduction'   => 0.0,
            'required_location_id'         => 0.0,
        ]
    ]

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

    public function isPurgatory() : bool {
        return $this->value === self::PURGATORY;
    }

    public function getMapModifers(): array {
        switch ($this->value) {
            case self::SURFACE:
            case self::LABYRINTH:
            case self::DUNGEONS:
                return [
                    'xp_bonus'                     => 0.0,
                    'skill_training_bonus'         => 0.0,
                    'drop_chance_bonus'            => 0.0,
                    'enemy_stat_bonus'             => 0.0,
                    'character_attack_reduction'   => 0.0,
                    'required_location_id'         => null,
                ];
            case self::SHADOW_PLANE:
                return [
                    'xp_bonus'                     => 0.05,
                    'skill_training_bonus'         => 0.05,
                    'drop_chance_bonus'            => 0.15,
                    'enemy_stat_bonus'             => 0.15,
                    'character_attack_reduction'   => 0.15,
                    'required_location_id'         => null,
                ];
            case self::HELL:
                return [
                    'xp_bonus'                     => 0.10,
                    'skill_training_bonus'         => 0.10,
                    'drop_chance_bonus'            => 0.25,
                    'enemy_stat_bonus'             => 0.25,
                    'character_attack_reduction'   => 0.20,
                    'required_location_id'         => null,
                ];
            case self::PURGATORY:
                return [
                    'xp_bonus'                     => 0.15,
                    'skill_training_bonus'         => 0.15,
                    'drop_chance_bonus'            => 0.30,
                    'enemy_stat_bonus'             => 0.30,
                    'character_attack_reduction'   => 0.25,
                    'required_location_id'         => Location::where('type', LocationType::),
                ];
        }
    }
}
