<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\BuildingInQueue;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\BuildingQueueType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UpgradeBuildingTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_cannot_push_building_above_max(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new UpgradeBuilding($building, $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(1, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
    }

    public function test_stale_queued_upgrade_cannot_complete_past_max(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 2,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $building->update([
            'level' => 2,
        ]);

        $job = new UpgradeBuilding($building->refresh(), $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
    }

    public function test_valid_completion_applies_queued_to_level(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 3,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new UpgradeBuilding($building, $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(3, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
    }

    public function test_null_from_level_upgrade_queue_does_not_mutate_building(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => null,
            'to_level' => 3,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new UpgradeBuilding($building, $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(1, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
    }

    public function test_stale_null_from_level_upgrade_queue_deletes_safely_without_spending_or_refunding_resources(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 1500,
                'current_clay' => 1500,
                'current_stone' => 1500,
                'current_iron' => 1500,
                'current_population' => 1500,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 2,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => null,
            'to_level' => 3,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new UpgradeBuilding($building, $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
        $this->assertSame(1500, $kingdom->refresh()->current_wood);
        $this->assertSame(1500, $kingdom->refresh()->current_clay);
        $this->assertSame(1500, $kingdom->refresh()->current_stone);
        $this->assertSame(1500, $kingdom->refresh()->current_iron);
        $this->assertSame(1500, $kingdom->refresh()->current_population);
    }

    public function test_stale_completion_where_current_level_differs_from_queue_from_level_does_not_mutate_building(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 2,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 3,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new UpgradeBuilding($building, $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
    }

    public function test_stale_completion_safely_removes_queue_and_does_not_spend_or_refund_resources_again(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 1500,
                'current_clay' => 1500,
                'current_stone' => 1500,
                'current_iron' => 1500,
                'current_population' => 1500,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 2,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 3,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new UpgradeBuilding($building, $character->user, $queue->id);
        $job->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityBuildingManagement::class),
            resolve(KingdomMaxResourceRecalculationService::class)
        );

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(BuildingInQueue::find($queue->id));
        $this->assertSame(1500, $kingdom->refresh()->current_wood);
        $this->assertSame(1500, $kingdom->refresh()->current_clay);
        $this->assertSame(1500, $kingdom->refresh()->current_stone);
        $this->assertSame(1500, $kingdom->refresh()->current_iron);
        $this->assertSame(1500, $kingdom->refresh()->current_population);
    }
}
