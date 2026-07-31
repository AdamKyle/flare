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

    public function test_claim_monster_death_creates_weekly_row_immediately(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $fight = WeeklyMonsterFight::where('character_id', $character->id)->where('monster_id', $monster->id)->first();

        $this->assertNotNull($fight);
        $this->assertTrue($fight->monster_was_killed);
    }

    public function test_claim_monster_death_marks_existing_death_row_killed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $fight = WeeklyMonsterFight::factory()->create(['character_id' => $character->id, 'monster_id' => $monster->id, 'character_deaths' => 2]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $this->assertTrue($fight->refresh()->monster_was_killed);
    }

    public function test_claiming_twice_retains_one_row(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);
        $service->claimMonsterDeath($character, $monster);

        $this->assertSame(1, WeeklyMonsterFight::where('character_id', $character->id)->where('monster_id', $monster->id)->count());
    }

    public function test_can_fight_monster_returns_false_immediately_after_claim(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $this->assertFalse($service->canFightMonster($character, $monster));
    }

    public function test_invalid_location_claim_does_not_create_row(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => null]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $service->claimMonsterDeath($character, $monster);

        $this->assertSame(0, WeeklyMonsterFight::where('character_id', $character->id)->count());
    }

    public function test_handle_monster_death_awards_once(): void
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

    public function test_successful_reward_sets_processed_timestamp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once();

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);

        $this->assertNotNull(WeeklyMonsterFight::where('character_id', $character->id)->first()->reward_processed_at);
    }

    public function test_reward_exception_leaves_processed_timestamp_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once()->andThrow(new RuntimeException('reward failed'));
        $this->expectException(RuntimeException::class);

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);
    }

    public function test_retry_after_failed_reward_processes_successfully(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::LORDS_STRONG_HOLD->value]);
        WeeklyMonsterFight::factory()->create(['character_id' => $character->id, 'monster_id' => $monster->id, 'monster_was_killed' => true, 'reward_processed_at' => null]);
        $handler = Mockery::mock(LocationSpecialtyHandler::class);
        $handler->shouldReceive('handleMonsterFromSpecialLocation')->once();

        (new WeeklyBattleService($handler))->handleMonsterDeath($character, $monster);

        $this->assertNotNull(WeeklyMonsterFight::where('character_id', $character->id)->first()->reward_processed_at);
    }

    public function test_inventory_capacity_failure_leaves_claim_available_for_reward_recovery(): void
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

    public function test_character_death_creates_count(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);

        (new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class)))->handleCharacterDeath($character, $monster);

        $this->assertSame(1, WeeklyMonsterFight::where('character_id', $character->id)->first()->character_deaths);
    }

    public function test_character_death_increments_existing_count(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $fight = WeeklyMonsterFight::factory()->create(['character_id' => $character->id, 'monster_id' => $monster->id, 'character_deaths' => 2]);

        (new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class)))->handleCharacterDeath($character, $monster);

        $this->assertSame(3, $fight->refresh()->character_deaths);
    }

    public function test_can_fight_unclaimed_monster(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);
        $service = new WeeklyBattleService(Mockery::mock(LocationSpecialtyHandler::class));

        $this->assertTrue($service->canFightMonster($character, $monster));
    }
}
