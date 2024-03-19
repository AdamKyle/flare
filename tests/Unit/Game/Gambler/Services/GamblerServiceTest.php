<?php

namespace Tests\Unit\Game\Gambler\Services;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Gambler\Handlers\SpinHandler;
use App\Game\Gambler\Services\GamblerService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class GamblerServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateGameSkill;

    private ?CharacterFactory $character;

    private ?GamblerService $gamblerService;

    public function setUp(): void {
        parent::setUp();

        $this->character      = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->gamblerService = resolve(GamblerService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character      = null;
        $this->gamblerService = null;
    }

    public function testHasEnoughGoldToSpin() {
        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $this->gamblerService->roll($character->refresh());

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
    }

    public function testDoesNotHasEnoughGoldToSpin() {
        $character = $this->character->getCharacter();

        $character->update(['gold' => 0]);

        $character = $character->refresh();

        $response = $this->gamblerService->roll($character->refresh());

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(422, $response['status']);
    }

    public function testFailedToMatchAny() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [1, 2, 3],
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

    public function testRolledAllThreeOfGoldDust() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [0, 0, 0],
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

    public function testRolledAllThreeOfShards() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [1, 1, 1],
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

    public function testRolledAllThreeOfCopperCoinsWithItem() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [2, 2, 2],
            'difference' => [2, 2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS
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

    public function testRolledAllThreeOfCopperCoinsWithoutItem() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [2, 2, 2],
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

    public function testRolledTwoOfGoldDust() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [0, 0, 1],
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

    public function testRolledTwoOfShards() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [1, 1, 2],
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

    public function testRolledTwoOfCopperCoinsWithItem() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [2, 2, 3],
            'difference' => [2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS
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

    public function testRolledTwoOfGoldDustWithMaxGoldDust() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [0, 0, 1],
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

    public function testRolledTwoOfShardsWithMaxShards() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [1, 1, 2],
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

    public function testRolledTwoOfCopperCoinsWithItemWithMaxCopperCoins() {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls'      => [2, 2, 3],
            'difference' => [2],
        ]);

        $this->app->instance(SpinHandler::class, $mock);
        $gamblerService = $this->app->make(GamblerService::class);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectsValue::GET_COPPER_COINS
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
