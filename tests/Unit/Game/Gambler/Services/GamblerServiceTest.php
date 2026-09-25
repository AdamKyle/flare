<?php

namespace Tests\Unit\Game\Gambler\Services;

use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Values\ItemEffectType;
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
        $this->gamblerService = new GamblerService(new SpinHandler(new PhpRandomNumberGenerator));
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

    public function test_character_in_cooldown_cannot_spin_again()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 1000000,
            'can_spin' => false,
            'can_spin_again_at' => now()->addSeconds(10),
        ]);

        $response = $this->gamblerService->roll($character->refresh());

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You must wait for the slot machine to cool down before spinning again.', $response['message']);
    }

    public function test_rejected_cooldown_spin_does_not_deduct_gold()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 1000000,
            'can_spin' => false,
            'can_spin_again_at' => now()->addSeconds(10),
        ]);

        $this->gamblerService->roll($character->refresh());

        $this->assertEquals(1000000, $character->refresh()->gold);
    }

    public function test_character_whose_cooldown_has_passed_can_spin()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 1000000,
            'can_spin' => false,
            'can_spin_again_at' => now()->subSecond(),
        ]);

        $response = $this->gamblerService->roll($character->refresh());

        $this->assertEquals(200, $response['status']);
    }

    public function test_valid_spin_costs_exactly_one_million_gold()
    {
        $character = $this->character->getCharacter();

        $character->update(['gold' => 1500000]);

        $this->gamblerService->roll($character->refresh());

        $this->assertEquals(500000, $character->refresh()->gold);
    }

    public function test_slot_status_reports_remaining_cooldown()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'can_spin' => false,
            'can_spin_again_at' => now()->addSeconds(8),
        ]);

        $status = $this->gamblerService->getSlotStatus($character->refresh());

        $this->assertFalse($status['can_spin']);
        $this->assertGreaterThan(0, $status['timeout_for']);
        $this->assertLessThanOrEqual(8, $status['timeout_for']);
    }

    public function test_slot_status_reports_no_cooldown_when_character_can_spin()
    {
        $character = $this->character->getCharacter();

        $status = $this->gamblerService->getSlotStatus($character->refresh());

        $this->assertTrue($status['can_spin']);
        $this->assertEquals(0, $status['timeout_for']);
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
        $this->assertEquals('You need 1,000,000 Gold to spin the slot machine.', $response['message']);
    }

    public function test_slot_status_reports_the_spin_cost()
    {
        $status = $this->gamblerService->getSlotStatus($this->character->getCharacter());

        $this->assertSame(1000000, $status['spin_cost']);
    }

    public function test_failed_to_match_any()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 2, 3],
            'difference' => [],
        ]);

        $gamblerService = new GamblerService($mock);

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

        $gamblerService = new GamblerService($mock);

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

        $gamblerService = new GamblerService($mock);

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

        $gamblerService = new GamblerService($mock);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectType::GET_COPPER_COINS->value,
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

        $gamblerService = new GamblerService($mock);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectType::GET_COPPER_COINS->value,
        ]);

        $mercenarySlotItem = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectType::MERCENARY_SLOT_BONUS->value,
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

        $gamblerService = new GamblerService($mock);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectType::GET_COPPER_COINS->value,
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

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You do not have the quest item to get copper coins. Complete the quest: The Magic of Purgatory in Hell.', $response['message']);
        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_rolled_two_of_gold_dust()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [0, 0, 1],
            'difference' => [0],
        ]);

        $gamblerService = new GamblerService($mock);

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

        $gamblerService = new GamblerService($mock);

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

        $gamblerService = new GamblerService($mock);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectType::GET_COPPER_COINS->value,
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

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000, 'gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You matched Gold dust, but you are already at the maximum amount you can hold.', $response['message']);
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
    }

    public function test_rolled_two_of_shards_with_max_shards()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 1, 2],
            'difference' => [1],
        ]);

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000, 'shards' => CurrencyLimit::MAX_SHARDS]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You matched Shards, but you are already at the maximum amount you can hold.', $response['message']);
        $this->assertEquals(CurrencyLimit::MAX_SHARDS, $character->shards);
    }

    public function test_rolled_two_of_copper_coins_with_item_with_max_copper_coins()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 3],
            'difference' => [2],
        ]);

        $gamblerService = new GamblerService($mock);

        $item = $this->createItem([
            'name' => 'Copper Coins',
            'type' => 'quest',
            'effect' => ItemEffectType::GET_COPPER_COINS->value,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['gold' => 1000000, 'copper_coins' => CurrencyLimit::MAX_COPPER]);

        $character = $character->refresh();

        $response = $gamblerService->roll($character->refresh());

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('You matched Copper coins, but you are already at the maximum amount you can hold.', $response['message']);
        $this->assertEquals(CurrencyLimit::MAX_COPPER, $character->copper_coins);
    }

    public function test_losing_spin_returns_the_gold_left_after_the_spin_and_no_reward()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [1, 2, 3],
            'difference' => [],
        ]);

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1500000]);

        $response = $gamblerService->roll($character->refresh());

        $this->assertSame(500000, $response['gold']);
        $this->assertNull($response['reward']);
        $this->assertSame(500000, $character->refresh()->gold);
    }

    public function test_winning_spin_returns_the_credited_reward_and_resulting_balance()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [0, 0, 0],
            'difference' => [0, 0],
        ]);

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000, 'gold_dust' => 2000]);

        $response = $gamblerService->roll($character->refresh());

        $this->assertSame(0, $response['gold']);
        $this->assertSame([
            'currency' => 'gold_dust',
            'amount' => 5000,
            'balance' => 7000,
        ], $response['reward']);
    }

    public function test_winning_spin_near_the_currency_cap_reports_only_the_amount_actually_credited()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [0, 0, 0],
            'difference' => [0, 0],
        ]);

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000000, 'gold_dust' => CurrencyLimit::MAX_GOLD_DUST - 100]);

        $response = $gamblerService->roll($character->refresh());

        $this->assertSame('You got a 100 Gold dust!', $response['message']);
        $this->assertSame([
            'currency' => 'gold_dust',
            'amount' => 100,
            'balance' => CurrencyLimit::MAX_GOLD_DUST,
        ], $response['reward']);
        $this->assertSame(CurrencyLimit::MAX_GOLD_DUST, $character->refresh()->gold_dust);
    }

    public function test_copper_coin_match_without_the_quest_item_reports_no_reward_and_the_gold_left()
    {
        $mock = Mockery::mock(SpinHandler::class)->makePartial();

        $mock->shouldReceive('roll')->andReturn([
            'rolls' => [2, 2, 2],
            'difference' => [2, 2],
        ]);

        $gamblerService = new GamblerService($mock);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1200000]);

        $response = $gamblerService->roll($character->refresh());

        $this->assertSame(200000, $response['gold']);
        $this->assertNull($response['reward']);
        $this->assertSame(0, $character->refresh()->copper_coins);
    }
}
