<?php

namespace Tests\Unit\Game\Raids\Services;

use App\Game\Raids\Services\RaidIdentityQueryService;
use App\Game\Raids\Values\RaidType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;

class RaidIdentityQueryServiceTest extends TestCase
{
    use CreateLocation, CreateMonster, CreateRaid, RefreshDatabase;

    private ?RaidIdentityQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RaidIdentityQueryService;
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_for_id_returns_the_identity_snapshot_for_a_raid_with_a_raid_type(): void
    {
        $monster = $this->createMonster();
        $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $location->id,
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $identity = $this->service->forId($raid->id);

        $this->assertNotNull($identity);
        $this->assertSame($raid->id, $identity->id);
        $this->assertSame(RaidType::ICE_QUEEN, $identity->raidType);
        $this->assertSame('Ice Queen Raid', $identity->name);
    }

    public function test_for_id_returns_null_when_the_raid_has_no_raid_type(): void
    {
        $monster = $this->createMonster();
        $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $location->id,
        ]);

        $this->assertNull($this->service->forId($raid->id));
    }

    public function test_for_id_returns_null_when_no_raid_exists(): void
    {
        $this->assertNull($this->service->forId(999999));
    }
}
