<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Tops\Events\CharacterTopsInspectionUpdated;
use App\Game\Tops\Events\CharacterTopsUpdated;
use App\Game\Tops\Events\DelveTopsUpdated;
use App\Game\Tops\Events\ExplorationTopsUpdated;
use App\Game\Tops\Events\FactionLoyaltyTopsUpdated;
use App\Game\Tops\Events\KingdomTopsUpdated;
use Tests\TestCase;

class TopsEventsTest extends TestCase
{
    public function test_character_tops_updated_carries_whitelisted_leaderboard_payload(): void
    {
        $event = new CharacterTopsUpdated(['rows' => [['character_name' => 'Hero']]]);

        $this->assertSame('Hero', $event->leaderboard['rows'][0]['character_name']);
    }

    public function test_character_inspection_updated_carries_profile_payload(): void
    {
        $event = new CharacterTopsInspectionUpdated(10, ['overview' => ['name' => 'Hero']]);

        $this->assertSame(10, $event->characterId);
        $this->assertSame('Hero', $event->profile['overview']['name']);
    }

    public function test_exploration_tops_updated_carries_leaderboard_payload(): void
    {
        $event = new ExplorationTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }

    public function test_delve_tops_updated_carries_leaderboard_payload(): void
    {
        $event = new DelveTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }

    public function test_faction_loyalty_tops_updated_carries_leaderboard_payload(): void
    {
        $event = new FactionLoyaltyTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }

    public function test_kingdom_tops_updated_carries_leaderboard_payload(): void
    {
        $event = new KingdomTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }
}
