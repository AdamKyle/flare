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

    public function testCompletionCannotPushBuildingAboveMax(): void
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

    public function testStaleQueuedUpgradeCannotCompletePastMax(): void
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

    public function testValidCompletionAppliesQueuedToLevel(): void
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

    public function testNullFromLevelUpgradeQueueDoesNotMutateBuilding(): void
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

    public function testStaleNullFromLevelUpgradeQueueDeletesSafelyWithoutSpendingOrRefundingResources(): void
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

    public function testStaleCompletionWhereCurrentLevelDiffersFromQueueFromLevelDoesNotMutateBuilding(): void
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

    public function testStaleCompletionSafelyRemovesQueueAndDoesNotSpendOrRefundResourcesAgain(): void
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
