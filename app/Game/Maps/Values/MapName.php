<?php

namespace App\Game\Maps\Values;

use App\Flare\Models\Location;
use App\Game\Events\Values\EventType;

enum MapName: string
{
    case SURFACE = 'Surface';
    case LABYRINTH = 'Labyrinth';
    case DUNGEONS = 'Dungeons';
    case SHADOW_PLANE = 'Shadow Plane';
    case HELL = 'Hell';
    case PURGATORY = 'Purgatory';
    case TWISTED_MEMORIES = 'Twisted Memories';
    case ICE_PLANE = 'The Ice Plane';
    case DELUSIONAL_MEMORIES = 'Delusional Memories';

    public static function values(): array
    {
        return array_combine(
            array_map(static fn (self $map): string => $map->value, self::cases()),
            array_map(static fn (self $map): string => $map->value, self::cases()),
        );
    }

    public static function kingdomColors(): array
    {
        return [
            self::SURFACE->value => '#879bc2',
            self::LABYRINTH->value => '#ff99c4',
            self::DUNGEONS->value => '#10eb2e',
            self::SHADOW_PLANE->value => '#000000',
            self::HELL->value => '#1194d1',
            self::PURGATORY->value => '#000000',
            self::ICE_PLANE->value => '#cebeeb',
            self::TWISTED_MEMORIES->value => '#9ae660',
            self::DELUSIONAL_MEMORIES->value => '#288f22',
        ];
    }

    public function isSurface(): bool
    {
        return $this === self::SURFACE;
    }

    public function isLabyrinth(): bool
    {
        return $this === self::LABYRINTH;
    }

    public function isDungeons(): bool
    {
        return $this === self::DUNGEONS;
    }

    public function isShadowPlane(): bool
    {
        return $this === self::SHADOW_PLANE;
    }

    public function isHell(): bool
    {
        return $this === self::HELL;
    }

    public function isPurgatory(): bool
    {
        return $this === self::PURGATORY;
    }

    public function isTwistedMemories(): bool
    {
        return $this === self::TWISTED_MEMORIES;
    }

    public function isDelusionalMemories(): bool
    {
        return $this === self::DELUSIONAL_MEMORIES;
    }

    public function isTheIcePlane(): bool
    {
        return $this === self::ICE_PLANE;
    }

    public function getMapModifers(): array
    {
        return match ($this) {
            self::SHADOW_PLANE => $this->modifiers(0.05, 0.05, 0.15, 0.15, 0.15),
            self::HELL => $this->modifiers(0.10, 0.10, 0.25, 0.25, 0.20),
            self::PURGATORY => $this->modifiers(
                0.15,
                0.15,
                0.30,
                0.30,
                0.25,
                Location::where('type', LocationType::TEAR_FABRIC_TIME->value)->first()->id,
            ),
            self::ICE_PLANE => $this->modifiers(0.50, 0.50, 0.30, 0.35, 0.30, null, true, EventType::WINTER_EVENT),
            self::TWISTED_MEMORIES => $this->modifiers(0.60, 0.40, 0.35, 0.45, 0.35, null, false),
            self::DELUSIONAL_MEMORIES => $this->modifiers(0.65, 0.45, 0.40, 0.50, 0.40, null, true, EventType::DELUSIONAL_MEMORIES_EVENT),
            self::SURFACE, self::LABYRINTH, self::DUNGEONS => $this->modifiers(0.0, 0.0, 0.0, 0.0, 0.0),
        };
    }

    private function modifiers(
        float $xpBonus,
        float $skillTrainingBonus,
        float $dropChanceBonus,
        float $enemyStatBonus,
        float $characterAttackReduction,
        ?int $requiredLocationId = null,
        bool $canTraverse = true,
        ?int $onlyDuringEventType = null,
    ): array {
        $modifiers = [
            'xp_bonus' => $xpBonus,
            'skill_training_bonus' => $skillTrainingBonus,
            'drop_chance_bonus' => $dropChanceBonus,
            'enemy_stat_bonus' => $enemyStatBonus,
            'character_attack_reduction' => $characterAttackReduction,
            'required_location_id' => $requiredLocationId,
            'can_traverse' => $canTraverse,
        ];

        if (! is_null($onlyDuringEventType)) {
            $modifiers['only_during_event_type'] = $onlyDuringEventType;
        }

        return $modifiers;
    }
}
