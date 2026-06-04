<?php

namespace Tests\Unit\Flare\Calculators;

use App\Flare\Calculators\GoldRushCheckCalculator;
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

    public function test_base_gold_rush_procs_at_boundary(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(100);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance());
    }

    public function test_base_gold_rush_does_not_proc_outside_boundary(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(101);

        $this->assertFalse(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance());
    }

    public function test_map_drop_bonus_increases_gold_rush_chance(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(1600);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance(0.15));
    }

    public function test_special_location_drop_bonus_increases_gold_rush_chance(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(600);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance(
            0.0,
            0.05
        ));
    }

    public function test_combined_gold_rush_chance_clamps_at_one_hundred_percent(): void
    {
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->with(10000)->andReturn(10000);

        $this->assertTrue(resolve(GoldRushCheckCalculator::class)->fetchGoldRushChance(0.60, 0.60));
    }

    public function test_gold_rush_adds_five_percent_of_actual_battle_gold_gained(): void
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

    public function test_gold_rush_does_not_add_five_percent_of_total_owned_gold(): void
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

    public function test_gold_rush_does_not_roll_when_actual_battle_gold_gained_is_zero(): void
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

    public function test_gold_rush_respects_max_gold_cap(): void
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

    public function test_gold_rush_uses_map_drop_bonus_for_chance(): void
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

    public function test_gold_rush_uses_special_location_drop_bonus_for_chance(): void
    {
        Event::fake();

        GoldRushCheckCalculatorFacade::shouldReceive('fetchGoldRushChance')->once()->with(0.0, 0.05)->andReturnFalse();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'enemy_strength_increase' => 0.30,
        ]);

        resolve(GoldRush::class)->processPotentialGoldRush($character->refresh(), 1000);

        $this->assertEquals($character->gold, $character->refresh()->gold);
    }
}
