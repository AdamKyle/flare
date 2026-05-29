<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Values\GemTierValue;
use App\Game\Gems\Values\GemTypeValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class GemServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GemService $gemService;

    private ?GameSkill $gemSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING->value,
            'max_level' => 100,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->gemSkill
        )->givePlayerLocation();

        $this->gemService = resolve(GemService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->gemSkill = null;
        $this->gemService = null;
    }

    public function test_cannot_afford_tier()
    {
        $character = $this->character->getCharacter();

        $result = $this->gemService->generateGem($character, 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have the required currencies to craft this item.', $result['message']);
    }

    public function test_cannot_craft_when_inventory_is_full()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'inventory_max' => 0,
        ]);

        $result = $this->gemService->generateGem($character, 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have enough space in your inventory.', $result['message']);
    }

    public function test_cannot_craft_when_skill_level_required_to_high()
    {

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = $this->gemService->generateGem($character, 4);

        $this->assertEquals(200, $result['status']);
    }

    public function testGemTierChancesProduceExpectedDcs()
    {
        $tierOne = (new GemTierValue(GemTierValue::TIER_ONE))->maxForTier();
        $tierTwo = (new GemTierValue(GemTierValue::TIER_TWO))->maxForTier();
        $tierThree = (new GemTierValue(GemTierValue::TIER_THREE))->maxForTier();
        $tierFour = (new GemTierValue(GemTierValue::TIER_FOUR))->maxForTier();

        $this->assertEqualsWithDelta(25.0, 100 - ($tierOne['chance'] * 100), 0.0001);
        $this->assertEqualsWithDelta(45.0, 100 - ($tierTwo['chance'] * 100), 0.0001);
        $this->assertEqualsWithDelta(65.0, 100 - ($tierThree['chance'] * 100), 0.0001);
        $this->assertEqualsWithDelta(75.0, 100 - ($tierFour['chance'] * 100), 0.0001);
    }

    public function testHighSkillBonusGuaranteesGemCraftingSuccess()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->skills()->where('game_skill_id', $this->gemSkill->id)->update([
            'level' => 76,
        ]);

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $gemService = Mockery::mock(GemService::class, [resolve(GemBuilder::class)], function (MockInterface $mock) {
            $mock->makePartial()
                ->shouldAllowMockingProtectedMethods()
                ->shouldReceive('canCraft')
                ->once()
                ->with(Mockery::on(function ($skill) {
                    return min(1.0, .25 + $skill->skill_bonus) === 1.0;
                }), .25)
                ->andReturn(true);
        });

        $result = $gemService->generateGem($character->refresh(), 4);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
    }

    public function testLowRollSucceedsWhenEffectiveChanceIsOne()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->skills()->where('game_skill_id', $this->gemSkill->id)->update([
            'level' => 26,
        ]);

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $gemService = Mockery::mock(GemService::class, [resolve(GemBuilder::class)], function (MockInterface $mock) {
            $mock->makePartial()
                ->shouldAllowMockingProtectedMethods()
                ->shouldReceive('canCraft')
                ->once()
                ->with(Mockery::on(function ($skill) {
                    return min(1.0, .75 + $skill->skill_bonus) === 1.0;
                }), .75)
                ->andReturn(true);
        });

        $result = $gemService->generateGem($character->refresh(), 1);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
    }

    public function testGemCraftingCanStillFailWhenRollExceedsEffectiveChance()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->gemSkill->update([
            'skill_bonus_per_level' => 0,
        ]);

        $character->skills()->where('game_skill_id', $this->gemSkill->id)->update([
            'level' => 75,
        ]);

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $gemService = Mockery::mock(GemService::class, [resolve(GemBuilder::class)], function (MockInterface $mock) {
            $mock->makePartial()
                ->shouldAllowMockingProtectedMethods()
                ->shouldReceive('canCraft')
                ->once()
                ->with(Mockery::on(function ($skill) {
                    return min(1.0, .25 + $skill->skill_bonus) === .25;
                }), .25)
                ->andReturn(false);
        });

        $result = $gemService->generateGem($character->refresh(), 4);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You failed to craft the gem, the item explodes before you into a pile of wasted effort and time.';
        });
    }

    public function testFailToCraftTheGem()
    {
        Event::fake();

        $this->instance(
            GemService::class,
            Mockery::mock(GemService::class, function (MockInterface $mock) {
                $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(false);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = resolve(GemService::class)->generateGem($character, 1);

        $this->assertEquals(200, $result['status']);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You failed to craft the gem, the item explodes before you into a pile of wasted effort and time.';
        });
    }

    public function test_attempt_to_craft_the_gem()
    {
        Event::fake();

        $gemService = Mockery::mock(GemService::class, [resolve(GemBuilder::class)], function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(false);
        });

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = $gemService->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);

        $this->assertEquals(200, $result['status']);
    }

    public function test_craft_the_gem()
    {
        Event::fake();

        $mock = Mockery::mock(GemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $mock->__construct(resolve(GemBuilder::class));

        $this->instance(
            GemService::class,
            $mock,
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = resolve(GemService::class)->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);

        $this->assertEquals(200, $result['status']);

        Event::assertDispatched(UpdateSkillEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_craft_the_gem_when_skill_level_is_maxed()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->skills()->where('game_skill_id', GameSkill::where('name', 'Gem Crafting')->first()->id)->update([
            'level' => 400,
        ]);

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = resolve(GemService::class)->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);

        $this->assertEquals(200, $result['status']);

        Event::assertNotDispatched(UpdateSkillEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_craft_the_gem_but_increase_the_amount()
    {
        Event::fake();

        $gemService = Mockery::mock(GemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $gem = $this->createGem([
            'name' => 'Sample',
            'tier' => 1,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::ICE,
            'primary_atonement_amount' => 0.10,
            'secondary_atonement_amount' => 0.10,
            'tertiary_atonement_amount' => 0.10,
        ]);

        $gemBuilder = Mockery::mock(GemBuilder::class, function (MockInterface $mock) use ($gem) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('buildGem')->once()->andReturn($gem);
        });

        $gemService->__construct($gemBuilder);

        $character = $this->character->getCharacter();

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gem->id,
            'amount' => 1,
        ]);

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = $gemService->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertEquals(2, $character->gemBag->gemSlots->first()->amount);

        $this->assertEquals(200, $result['status']);

        Event::assertDispatched(UpdateSkillEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_get_craftable_gems_list()
    {
        $character = $this->character->getCharacter();

        $result = $this->gemService->getCraftableTiers($character);

        $this->assertNotEmpty($result);
    }

    public function test_throw_exception_when_the_player_does_not_have_the_skill()
    {
        $this->expectException(Exception::class);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->gemService->getCraftableTiers($character);
    }

    public function test_fetch_character_gem_crafting_xp()
    {
        $character = $this->character->getCharacter();

        $gemCraftingXPData = $this->gemService->fetchSkillXP($character);

        $gemCraftingSkill = $character->skills()->where('game_skill_id', $this->gemSkill->id)->first();

        $expected = [
            'current_xp' => 0,
            'next_level_xp' => $gemCraftingSkill->xp_max,
            'skill_name' => $gemCraftingSkill->baseSkill->name,
            'level' => $gemCraftingSkill->level,
        ];

        $this->assertEquals($gemCraftingXPData, $expected);
    }
}
