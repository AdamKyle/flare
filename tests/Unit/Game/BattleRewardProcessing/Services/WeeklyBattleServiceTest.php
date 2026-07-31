<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\WeeklyMonsterFight;
use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Handlers\LocationSpecialtyHandler;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class WeeklyBattleServiceTest extends TestCase
{
    use CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testClaimMonsterDeathCreatesWeeklyRowImmediately(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $fight = WeeklyMonsterFight::where('character_id', $character->id)->where('monster_id', $monster->id)->first();

        $this->assertNotNull($fight);
        $this->assertTrue($fight->monster_was_killed);
    }

    public function testClaimMonsterDeathMarksExistingDeathRowKilled(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $fight = WeeklyMonsterFight::factory()->create(['character_id' => $character->id, 'monster_id' => $monster->id, 'character_deaths' => 2]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $this->assertTrue($fight->refresh()->monster_was_killed);
    }

    public function testClaimingTwiceRetainsOneRow(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);
        $service->claimMonsterDeath($character, $monster);

        $this->assertSame(1, WeeklyMonsterFight::where('character_id', $character->id)->where('monster_id', $monster->id)->count());
    }

    public function testCanFightMonsterReturnsFalseImmediatelyAfterClaim(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $this->assertFalse($service->canFightMonster($character, $monster));
    }

    public function testInvalidLocationClaimDoesNotCreateRow(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => null]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $this->assertSame(0, WeeklyMonsterFight::where('character_id', $character->id)->count());
    }

    public function testHandleMonsterDeathAwardsOnce(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once()->with(Mockery::type(get_class($character)), Mockery::type(WeeklyMonsterFight::class), false);
        $service = new WeeklyBattleService($handler);

        $service->handleMonsterDeath($character, $monster);
        $service->handleMonsterDeath($character, $monster);

        $this->assertSame(1, WeeklyMonsterFight::where('character_id', $character->id)->whereNotNull('reward_processed_at')->count());
    }

    public function testSuccessfulRewardSetsProcessedTimestamp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once();

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);

        $this->assertNotNull(WeeklyMonsterFight::where('character_id', $character->id)->first()->reward_processed_at);
    }

    public function testRewardExceptionLeavesProcessedTimestampNull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once()->andThrow(new RuntimeException('reward failed'));
        $this->expectException(RuntimeException::class);

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);
    }

    public function testRetryAfterFailedRewardProcessesSuccessfully(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        WeeklyMonsterFight::factory()->create(['character_id' => $character->id, 'monster_id' => $monster->id, 'monster_was_killed' => true, 'reward_processed_at' => null]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once();

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);

        $this->assertNotNull(WeeklyMonsterFight::where('character_id', $character->id)->first()->reward_processed_at);
    }

    public function testInventoryCapacityFailureLeavesClaimAvailableForRewardRecovery(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')
            ->once()
            ->andReturnUsing(function () use ($character, $monster): void {
                $fight = WeeklyMonsterFight::where('character_id', $character->id)
                    ->where('monster_id', $monster->id)
                    ->firstOrFail();
                $this->assertTrue($fight->monster_was_killed);
                $this->assertNull($fight->reward_processed_at);

                throw new RuntimeException('Weekly reward delivery requires four available inventory slots.');
            });
        $this->expectException(RuntimeException::class);

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);
    }

    public function testCharacterDeathCreatesCount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);

        (new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class)))->handleCharacterDeath($character, $monster);

        $this->assertSame(1, WeeklyMonsterFight::where('character_id', $character->id)->first()->character_deaths);
    }

    public function testCharacterDeathIncrementsExistingCount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $fight = WeeklyMonsterFight::factory()->create(['character_id' => $character->id, 'monster_id' => $monster->id, 'character_deaths' => 2]);

        (new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class)))->handleCharacterDeath($character, $monster);

        $this->assertSame(3, $fight->refresh()->character_deaths);
    }
}
