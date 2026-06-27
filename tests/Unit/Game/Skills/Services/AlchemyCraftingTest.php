<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\SkillCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class AlchemyCraftingTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?Item $alchemyItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->alchemyItem = $this->createItem([
            'gold_dust_cost' => 1000,
            'shards_cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->alchemyItem = null;
    }

    public function test_alchemy_crafting_writes_to_alchemy_bag_slot(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $alchemyService = $this->app->make(AlchemyService::class);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);

        $alchemyBag = $character->refresh()->alchemyBag;

        $this->assertNotNull($alchemyBag);
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->count());
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->where('item_id', $this->alchemyItem->id)->value('amount'));
    }

    public function test_alchemy_crafting_increments_amount_when_same_item_exists(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->twice()->andReturn(1);
                $mock->shouldReceive('characterRoll')->twice()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $alchemyService = $this->app->make(AlchemyService::class);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);

        $alchemyBag = $character->refresh()->alchemyBag;

        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->count());
        $this->assertEquals(2, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->where('item_id', $this->alchemyItem->id)->value('amount'));
    }

    public function test_alchemy_crafting_fails_when_alchemy_bag_is_full(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'alchemy_bag_limit' => 1,
        ]);

        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->alchemyItem->id,
            'amount' => 1,
        ]);

        $alchemyService = $this->app->make(AlchemyService::class);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Your Alchemy Bag is full. Use or remove alchemy items before crafting more.';
        });

        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->value('amount'));
    }

    public function test_alchemy_crafting_succeeds_when_current_count_plus_one_equals_limit(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'alchemy_bag_limit' => 5,
        ]);

        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 4,
        ]);

        $alchemyService = $this->app->make(AlchemyService::class);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);

        $alchemyBag = $character->refresh()->alchemyBag;

        $this->assertEquals(5, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->sum('amount'));
    }

    public function test_alchemy_crafting_fails_when_current_count_plus_one_exceeds_limit(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'alchemy_bag_limit' => 5,
        ]);

        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $alchemyService = $this->app->make(AlchemyService::class);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Your Alchemy Bag is full. Use or remove alchemy items before crafting more.';
        });

        $this->assertEquals(5, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->sum('amount'));
    }

    public function test_alchemy_crafting_fails_when_stacking_existing_row_would_exceed_limit(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'alchemy_bag_limit' => 5,
        ]);

        $character = $character->refresh();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->alchemyItem->id,
            'amount' => 5,
        ]);

        $alchemyService = $this->app->make(AlchemyService::class);
        $alchemyService->transmute($character->refresh(), $this->alchemyItem->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Your Alchemy Bag is full. Use or remove alchemy items before crafting more.';
        });

        $this->assertEquals(5, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)
            ->where('item_id', $this->alchemyItem->id)
            ->value('amount'));
    }
}
