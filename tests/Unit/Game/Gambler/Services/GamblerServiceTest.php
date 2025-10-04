<?php

namespace Tests\Unit\Game\Gambler\Services;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Events\Values\EventType;
use App\Game\Gambler\Handlers\SpinHandler;
use App\Game\Gambler\Services\GamblerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class GamblerServiceTest extends TestCase
{
    use CreateEvent, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GamblerService $gamblerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->gamblerService = resolve(GamblerService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->gamblerService = null;
    }

    public function test_has_enough_gold_to_spin()
    {
        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $this->gamblerService->roll($character->refresh());

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
    }

    public function test_does_not_has_enough_gold_to_spin()
    {
        $character = $this->character->getCharacter();

        $character->update(['gold' => 0]);

        $character = $character->refresh();

        $response = $this->gamblerService->roll($character->refresh());

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(422, $response['status']);
    }

    public function test_failed_to_match_any()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 2, 3],
            'difference' => [],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Darn! Better luck next time child! Spin again!', $response['message']);
    }

    public function test_rolled_all_three_of_gold_dust()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [0, 0, 0],
            'difference' => [0, 0],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 5,000 Gold dust!', $response['message']);
        $this->assertEquals(5000, $character->gold_dust);
    }

    public function test_rolled_all_three_of_shards()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 1, 1],
            'difference' => [1, 1],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 5,000 Shards!', $response['message']);
        $this->assertEquals(5000, $character->shards);
    }

    public function test_rolled_all_three_of_copper_coins_with_item()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 2],
            'difference' => [2, 2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 5,000 Copper coins!', $response['message']);
        $this->assertEquals(5000, $character->copper_coins);
    }

    public function test_rolled_all_three_of_copper_coins_with_item_and_quest_item_that_gives_bonus()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 2],
            'difference' => [2, 2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
        ]);

        $mercenarySlotItem = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::MERCENARY_SLOT_BONUS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem($mercenarySlotItem)->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertGreaterThan(5000, $character->copper_coins);
    }

    public function test_rolled_all_three_of_copper_coins_with_item_and_weekly_currency_event()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 2],
            'difference' => [2, 2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
        ]);

        $this->createEvent([
            'type' => EventType::WEEKLY_CURRENCY_DROPS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertGreaterThan(5000, $character->copper_coins);
    }

    public function test_rolled_all_three_of_copper_coins_without_item()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 2],
            'difference' => [2, 2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Your do not have the quest item to get copper coins. Complete the quest: The Magic of Purgatory in Hell.', $response['message']);
        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_rolled_two_of_gold_dust()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [0, 0, 1],
            'difference' => [0],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 1,000 Gold dust!', $response['message']);
        $this->assertEquals(1000, $character->gold_dust);
    }

    public function test_rolled_two_of_shards()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 1, 2],
            'difference' => [1],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 1,000 Shards!', $response['message']);
        $this->assertEquals(1000, $character->shards);
    }

    public function test_rolled_two_of_copper_coins_with_item()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 3],
            'difference' => [2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 1,000 Copper coins!', $response['message']);
        $this->assertEquals(1000, $character->copper_coins);
    }

    public function test_rolled_two_of_gold_dust_with_max_gold_dust()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [0, 0, 1],
            'difference' => [0],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000, 'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 1,000 Gold dust!', $response['message']);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
    }

    public function test_rolled_two_of_shards_with_max_shards()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 1, 2],
            'difference' => [1],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000, 'shards' => MaxCurrenciesValue::MAX_SHARDS]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 1,000 Shards!', $response['message']);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function test_rolled_two_of_copper_coins_with_item_with_max_copper_coins()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 3],
            'difference' => [2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['gold' => 1000000, 'copper_coins' => MaxCurrenciesValue::MAX_COPPER]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You got a 1,000 Copper coins!', $response['message']);
        $this->assertEquals(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
    }
}
