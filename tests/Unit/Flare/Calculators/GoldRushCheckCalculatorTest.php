<?php

namespace Tests\Unit\Flare\Calculators;

use App\Flare\Calculators\GoldRushCheckCalculator;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Services\GoldRush;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator as GoldRushCheckCalculatorFacade;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class GoldRushCheckCalculatorTest extends TestCase
{
    use CreateLocation, RefreshDatabase;

    public function testBaseGoldRushProcsAtBoundary(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(100);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance());
    }

    public function testBaseGoldRushDoesNotProcOutsideBoundary(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(101);

        $this->assertFalse(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance());
    }

    public function testMapDropBonusIncreasesGoldRushChance(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(1600);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance(0.15));
    }

    public function testSpecialLocationDropBonusIncreasesGoldRushChance(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(600);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance(
            0.0,
            (new LocationEffectValue(LocationEffectValue::INCREASE_STATS_BY_FIVE_HUNDRED))->fetchDropRate()
        ));
    }

    public function testCombinedGoldRushChanceClampsAtOneHundredPercent(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(10000);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance(0.60, 0.60));
    }

    public function testGoldRushAddsFivePercentOfActualBattleGoldGained(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->once()->with(0.0, 0.0)->andReturnTrue();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'gold' => 100000,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 1000);

        $this->assertEquals(100050, $character->refresh()->gold);
    }

    public function testGoldRushDoesNotAddFivePercentOfTotalOwnedGold(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->once()->with(0.0, 0.0)->andReturnTrue();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'gold' => 100000,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 2000);

        $this->assertEquals(100100, $character->refresh()->gold);
    }

    public function testGoldRushDoesNotRollWhenActualBattleGoldGainedIsZero(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->never();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'gold' => 100000,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 0);

        $this->assertEquals(100000, $character->refresh()->gold);
    }

    public function testGoldRushRespectsMaxGoldCap(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->once()->with(0.0, 0.0)->andReturnTrue();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD - 10,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 1000);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->refresh()->gold);
    }

    public function testGoldRushUsesMapDropBonusForChance(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->once()->with(0.15, 0.0)->andReturnFalse();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->map->gameMap()->update([
            'drop_chance_bonus' => 0.15,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 1000);

        $this->assertEquals($character->gold, $character->refresh()->gold);
    }

    public function testGoldRushUsesSpecialLocationDropBonusForChance(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->once()->with(0.0, 0.05)->andReturnFalse();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_FIVE_HUNDRED,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 1000);

        $this->assertEquals($character->gold, $character->refresh()->gold);
    }
}