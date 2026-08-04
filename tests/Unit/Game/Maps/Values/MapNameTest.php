<?php

namespace Tests\Unit\Game\Maps\Values;

use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\MapName;
use Tests\TestCase;

class MapNameTest extends TestCase
{
    public function test_kingdom_color_lookup_matches_configured_map(): void
    {
        $this->assertSame('#879bc2', MapName::kingdomColors()[MapName::SURFACE->value]);
        $this->assertSame('#288f22', MapName::kingdomColors()[MapName::DELUSIONAL_MEMORIES->value]);
    }

    public function test_surface_map_applies_no_modifiers_and_allows_traversal(): void
    {
        $this->assertSame([
            'xp_bonus' => 0.0,
            'skill_training_bonus' => 0.0,
            'drop_chance_bonus' => 0.0,
            'enemy_stat_bonus' => 0.0,
            'character_attack_reduction' => 0.0,
            'required_location_id' => null,
            'can_traverse' => true,
        ], MapName::SURFACE->getMapModifers());
    }

    public function test_twisted_memories_map_disallows_traversal(): void
    {
        $this->assertFalse(MapName::TWISTED_MEMORIES->getMapModifers()['can_traverse']);
    }

    public function test_ice_plane_and_delusional_memories_maps_are_restricted_to_their_events(): void
    {
        $this->assertSame(EventType::WINTER_EVENT, MapName::ICE_PLANE->getMapModifers()['only_during_event_type']);
        $this->assertSame(EventType::DELUSIONAL_MEMORIES_EVENT, MapName::DELUSIONAL_MEMORIES->getMapModifers()['only_during_event_type']);
    }
}
