<?php

namespace App\Flare\Values;

use App\Flare\Models\Location;
use App\Game\Events\Values\EventType;

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
    const TWISTED_MEMORIES       = 'Twisted Memories';

    // Event Specific Panes:
    const ICE_PLANE              = 'The Ice Plane';
    const DELUSIONAL_MEMORIES    = 'Delusional Memories';

    /**
     * @var array $values
     */
    public static $values = [
        'Surface'       => self::SURFACE,
        'Labyrinth'     => self::LABYRINTH,
        'Dungeons'      => self::DUNGEONS,
        'Shadow Plane'  => self::SHADOW_PLANE,
        'Hell'          => self::HELL,
        'Purgatory'     => self::PURGATORY,
        'Twisted Memories' => self::TWISTED_MEMORIES,
        'The Ice Plane' => self::ICE_PLANE,
        'Delusional Memories' => self::DELUSIONAL_MEMORIES,
    ];

    /**
     * @var array $kingdomColors
     */
    public static $kingdomColors = [
        self::SURFACE      => '#879bc2',
        self::LABYRINTH    => '#ff99c4',
        self::DUNGEONS     => '#10eb2e',
        self::SHADOW_PLANE => '#000000',
        self::HELL         => '#1194d1',
        self::PURGATORY    => '#000000',
        self::ICE_PLANE    => '#cebeeb',
        self::TWISTED_MEMORIES    => '#9ae660',
        self::DELUSIONAL_MEMORIES => '#288f22',
    ];

    /**
     * @var array $mapModifiers
     */
    public static array $mapModifiers = [
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
    ];

    /**
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function isSurface(): bool {
        return $this->value === self::SURFACE;
    }

    public function isLabyrinth(): bool {
        return $this->value === self::LABYRINTH;
    }

    public function isDungeons(): bool {
        return $this->value === self::DUNGEONS;
    }

    public function isShadowPlane(): bool {
        return $this->value === self::SHADOW_PLANE;
    }

    public function isHell(): bool {
        return $this->value === self::HELL;
    }

    public function isPurgatory(): bool {
        return $this->value === self::PURGATORY;
    }

    public function isTwistedMemories(): bool {
        return $this->value === self::TWISTED_MEMORIES;
    }

    public function isDelusionalMemories(): bool {
        return $this->value === self::DELUSIONAL_MEMORIES;
    }

    public function isTheIcePlane(): bool {
        return $this->value === self::ICE_PLANE;
    }

    public function getMapModifers(): array {
        switch ($this->value) {
            case self::SHADOW_PLANE:
                return [
                    'xp_bonus'                     => 0.05,
                    'skill_training_bonus'         => 0.05,
                    'drop_chance_bonus'            => 0.15,
                    'enemy_stat_bonus'             => 0.15,
                    'character_attack_reduction'   => 0.15,
                    'required_location_id'         => null,
                    'can_traverse'                 => true,
                ];
            case self::HELL:
                return [
                    'xp_bonus'                     => 0.10,
                    'skill_training_bonus'         => 0.10,
                    'drop_chance_bonus'            => 0.25,
                    'enemy_stat_bonus'             => 0.25,
                    'character_attack_reduction'   => 0.20,
                    'required_location_id'         => null,
                    'can_traverse'                 => true,
                ];
            case self::PURGATORY:
                return [
                    'xp_bonus'                     => 0.15,
                    'skill_training_bonus'         => 0.15,
                    'drop_chance_bonus'            => 0.30,
                    'enemy_stat_bonus'             => 0.30,
                    'character_attack_reduction'   => 0.25,
                    'required_location_id'         => Location::where('type', LocationType::TEAR_FABRIC_TIME)->first()->id,
                    'can_traverse'                 => true,
                ];
            case self::ICE_PLANE:
                return [
                    'xp_bonus'                     => 0.50,
                    'skill_training_bonus'         => 0.50,
                    'drop_chance_bonus'            => 0.30,
                    'enemy_stat_bonus'             => 0.35,
                    'character_attack_reduction'   => 0.30,
                    'only_during_event_type'       => EventType::WINTER_EVENT,
                    'required_location_id'         => null,
                    'can_traverse'                 => true,
                ];
            case self::TWISTED_MEMORIES:
                return [
                    'xp_bonus'                     => 0.60,
                    'skill_training_bonus'         => 0.40,
                    'drop_chance_bonus'            => 0.35,
                    'enemy_stat_bonus'             => 0.45,
                    'character_attack_reduction'   => 0.35,
                    'required_location_id'         => null,
                    'can_traverse'                 => false,
                ];
            case self::DELUSIONAL_MEMORIES:
                return [
                    'xp_bonus'                     => 0.65,
                    'skill_training_bonus'         => 0.45,
                    'drop_chance_bonus'            => 0.40,
                    'enemy_stat_bonus'             => 0.50,
                    'character_attack_reduction'   => 0.40,
                    'required_location_id'         => null,
                    'only_during_event_type'       => EventType::DELUSIONAL_MEMORIES_EVENT,
                    'can_traverse'                 => true,
                ];
            case self::SURFACE:
            case self::LABYRINTH:
            case self::DUNGEONS:
            default:
                return [
                    'xp_bonus'                     => 0.0,
                    'skill_training_bonus'         => 0.0,
                    'drop_chance_bonus'            => 0.0,
                    'enemy_stat_bonus'             => 0.0,
                    'character_attack_reduction'   => 0.0,
                    'required_location_id'         => null,
                    'can_traverse'                 => true,
                ];
        }
    }
}
