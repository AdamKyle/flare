<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Skills\Services\SkillCheckService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Values\CharacterClassValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Values\SkillTypeValue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class AlchemyServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateClass, CreateGameSkill;

    private ?CharacterFactory $character;

    private ?AlchemyService $alchemyService;

    private ?Item $alchemyItem;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->alchemyService = resolve(AlchemyService::class);

        $this->alchemyItem = $this->createItem([
            'gold_dust_cost'       => 1000,
            'shards_cost'          => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 100,
            'crafting_type'        => 'alchemy',
            'can_craft'            => true,
            'type'                 => 'alchemy'
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->alchemyService = null;
        $this->alchemyItem = null;
    }

    public function testGetAlchemyItemsForCrafting() {
        $character = $this->character->getCharacter();

        $result = $this->alchemyService->fetchAlchemistItems($character);

        $this->assertNotEmpty($result);
    }

    public function testGetAlchemyItemsAsAlchemistForTheCostReduction() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST
        ]))->assignSkill($this->createGameSkill([
            'type' => SkillTypeValue::ALCHEMY,
        ]), 10)->givePlayerLocation()->getCharacter();

        $result = $this->alchemyService->fetchAlchemistItems($character);

        $this->assertNotEmpty($result);
        $this->assertNotEquals($result[0]->gold_dust_cost, $this->alchemyItem->gold_dust_cost);
        $this->assertNotEquals($result[0]->shards_cost, $this->alchemyItem->shards_cost);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testGetAlchemyItemsAsMerchantForTheCostReduction() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT
        ]))->assignSkill($this->createGameSkill([
            'type' => SkillTypeValue::ALCHEMY,
        ]), 10)->givePlayerLocation()->getCharacter();

        $result = $this->alchemyService->fetchAlchemistItems($character);

        $this->assertNotEmpty($result);
        $this->assertNotEquals($result[0]->gold_dust_cost, $this->alchemyItem->gold_dust_cost);
        $this->assertNotEquals($result[0]->shards_cost, $this->alchemyItem->shards_cost);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCannotTransmuteItemThatDoesntExist() {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->alchemyService->transmute($character, 10);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCannotTransmuteItemNotEnoughGoldDust() {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->alchemyService->transmute($character, $this->alchemyItem->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('not_enough_gold_dust');
        });
    }

    public function testCannotTransmuteItemNotEnoughShards() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $this->alchemyService->transmute($character, $this->alchemyItem->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('not_enough_shards');
        });
    }

    public function testCannotTransmuteItemLevelToHigh() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $this->alchemyItem->update([
            'skill_level_required' => 500,
        ]);

        $this->alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('to_hard_to_craft');
        });
    }

    public function testTransmuteWhenLevelTrivialTooLow() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $this->alchemyItem->update([
            'skill_level_trivial' => -10,
        ]);

        $this->alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('to_easy_to_craft');
        });

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);
    }

    public function testTransmute() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $this->alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function testTransmuteAndFail() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $alchemyService = $this->app->make(AlchemyService::class);

        $alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('failed_to_transmute');
        });
    }

    public function testTransmuteAndSucceed() {
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
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $alchemyService = $this->app->make(AlchemyService::class);

        $alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        $this->assertCount(1, $character->inventory->slots);
    }

    public function testTransmuteAndSucceedButInventoryIsFull() {
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
            'gold_dust'     => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'        => MaxCurrenciesValue::MAX_SHARDS,
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $alchemyService = $this->app->make(AlchemyService::class);

        $alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('inventory_full');
        });

        $this->assertCount(0, $character->inventory->slots);
    }

    public function testTransmuteAsAlchemist() {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $goldDustAfterOriginalCost = MaxCurrenciesValue::MAX_GOLD_DUST - $this->alchemyItem->gold_dust_cost;
        $shardsAfterOriginalCost   = MaxCurrenciesValue::MAX_SHARDS - $this->alchemyItem->shards_cost;

        $character = $character->refresh();

        $alchemyService = $this->app->make(AlchemyService::class);

        $alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);
        $this->assertGreaterThan($goldDustAfterOriginalCost, $character->gold_dust);
        $this->assertGreaterThan($shardsAfterOriginalCost, $character->shards);
    }

    public function testTransmuteAsMerchant() {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'    => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $goldDustAfterOriginalCost = MaxCurrenciesValue::MAX_GOLD_DUST - $this->alchemyItem->gold_dust_cost;
        $shardsAfterOriginalCost   = MaxCurrenciesValue::MAX_SHARDS - $this->alchemyItem->shards_cost;

        $character = $character->refresh();

        $alchemyService = $this->app->make(AlchemyService::class);

        $alchemyService->transmute($character, $this->alchemyItem->refresh()->id);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);
        $this->assertGreaterThan($goldDustAfterOriginalCost, $character->gold_dust);
        $this->assertGreaterThan($shardsAfterOriginalCost, $character->shards);
    }
}
