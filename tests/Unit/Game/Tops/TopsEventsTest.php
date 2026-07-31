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
    public function testCharacterTopsUpdatedCarriesWhitelistedLeaderboardPayload(): void
    {
        $event = new CharacterTopsUpdated(['rows' => [['character_name' => 'Hero']]]);

        $this->assertSame('Hero', $event->leaderboard['rows'][0]['character_name']);
    }

    public function testCharacterInspectionUpdatedCarriesProfilePayload(): void
    {
        $event = new CharacterTopsInspectionUpdated(10, ['overview' => ['name' => 'Hero']]);

        $this->assertSame(10, $event->characterId);
        $this->assertSame('Hero', $event->profile['overview']['name']);
    }

    public function testExplorationTopsUpdatedCarriesLeaderboardPayload(): void
    {
        $event = new ExplorationTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }

    public function testDelveTopsUpdatedCarriesLeaderboardPayload(): void
    {
        $event = new DelveTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }

    public function testFactionLoyaltyTopsUpdatedCarriesLeaderboardPayload(): void
    {
        $event = new FactionLoyaltyTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }

    public function testKingdomTopsUpdatedCarriesLeaderboardPayload(): void
    {
        $event = new KingdomTopsUpdated(['rows' => []]);

        $this->assertSame([], $event->leaderboard['rows']);
    }
}
