<?php

namespace Tests\Unit\Game\Events\Services;

use App\Game\Events\Services\FactionLoyaltyPledgeCleanupService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;

class FactionLoyaltyPledgeCleanupServiceTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameMap, CreateNpc, RefreshDatabase;

    private ?FactionLoyaltyPledgeCleanupService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app()->make(FactionLoyaltyPledgeCleanupService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_unpledge_if_on_faction_with_null_faction_does_nothing(): void
    {
        $surface = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => true]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $surface)
            ->getCharacter();

        $faction = $character->factions->where('game_map_id', $surface->id)->first();
        $loyalty = $this->createFactionLoyalty([
            'faction_id' => $faction->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $mock = Mockery::mock(FactionLoyaltyService::class, function (MockInterface $m) {
            $m->shouldNotReceive('stopAssistingNpc');
            $m->shouldNotReceive('removePledge');
        });
        $this->instance(FactionLoyaltyService::class, $mock);
        $this->service = app()->make(FactionLoyaltyPledgeCleanupService::class);

        $this->service->unpledgeIfOnFaction($character, null);

        $this->assertTrue($loyalty->fresh()->is_pledged);
    }

    public function test_unpledge_if_on_faction_when_no_loyalty_record_does_nothing(): void
    {
        $surface = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => true]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $surface)
            ->getCharacter();

        $faction = $character->factions->where('game_map_id', $surface->id)->first();

        $mock = Mockery::mock(FactionLoyaltyService::class, function (MockInterface $m) {
            $m->shouldNotReceive('stopAssistingNpc');
            $m->shouldNotReceive('removePledge');
        });
        $this->instance(FactionLoyaltyService::class, $mock);
        $this->service = app()->make(FactionLoyaltyPledgeCleanupService::class);

        $this->service->unpledgeIfOnFaction($character, $faction);

        $this->assertFalse(
            $character->factionLoyalties()
                ->where('faction_id', $faction->id)
                ->exists()
        );
    }

    public function test_unpledge_if_on_faction_when_loyalty_exists_without_assisting_npc_removes_pledge_only(): void
    {
        $surface = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => true]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $surface)
            ->getCharacter();

        $faction = $character->factions->where('game_map_id', $surface->id)->first();

        $this->createFactionLoyalty([
            'faction_id' => $faction->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $calls = [];
        $mock = Mockery::mock(FactionLoyaltyService::class, function (MockInterface $m) use ($character, $faction, &$calls) {
            $m->shouldNotReceive('stopAssistingNpc');
            $m->shouldReceive('removePledge')->once()->withArgs(function ($c, $f) use ($character, $faction) {
                return $c->id === $character->id && $f->id === $faction->id;
            })->andReturnUsing(function () use (&$calls): array {
                $calls[] = 'remove_pledge';

                return [];
            });
        });
        $this->instance(FactionLoyaltyService::class, $mock);
        $this->service = app()->make(FactionLoyaltyPledgeCleanupService::class);

        $this->service->unpledgeIfOnFaction($character, $faction);

        $this->assertSame(['remove_pledge'], $calls);
    }

    public function test_unpledge_if_on_faction_when_assisting_npc_stops_assistance_then_removes_pledge(): void
    {
        $surface = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => true]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $surface)
            ->getCharacter();

        $faction = $character->factions->where('game_map_id', $surface->id)->first();

        $loyalty = $this->createFactionLoyalty([
            'faction_id' => $faction->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $npc = $this->createNpc(['game_map_id' => $surface->id]);

        $assistingNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $loyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $calls = [];
        $mock = Mockery::mock(FactionLoyaltyService::class, function (MockInterface $m) use ($character, $assistingNpc, $faction, &$calls) {
            $m->shouldReceive('stopAssistingNpc')->once()->withArgs(function ($c, $n) use ($character, $assistingNpc) {
                return $c->id === $character->id && $n->id === $assistingNpc->id;
            })->ordered()->andReturnUsing(function () use (&$calls): array {
                $calls[] = 'stop_assisting_npc';

                return [];
            });
            $m->shouldReceive('removePledge')->once()->withArgs(function ($c, $f) use ($character, $faction) {
                return $c->id === $character->id && $f->id === $faction->id;
            })->ordered()->andReturnUsing(function () use (&$calls): array {
                $calls[] = 'remove_pledge';

                return [];
            });
        });
        $this->instance(FactionLoyaltyService::class, $mock);
        $this->service = app()->make(FactionLoyaltyPledgeCleanupService::class);

        $this->service->unpledgeIfOnFaction($character, $faction);

        $this->assertSame([
            'stop_assisting_npc',
            'remove_pledge',
        ], $calls);
    }
}
