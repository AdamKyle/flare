<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameGemAbility;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GemBagSlot;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Values\GemTierValue;
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
use Tests\Setup\Skills\GemServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class GemServiceTest extends TestCase
{
    use CreateClass, CreateGameGemAbility, CreateGameSkill, CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

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
        $this->createGameGemAbility();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->gemSkill
        )->givePlayerLocation();

        $this->gemService = (new GemServiceFactory)->build();
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

    public function test_tier_one_without_enabled_abilities_fails_before_payment(): void
    {
        $character = $this->character->getCharacter();
        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);
        $character->refresh();
        $startingGoldDust = $character->gold_dust;
        $startingShards = $character->shards;
        $startingCopper = $character->copper_coins;
        $this->createGameGemAbility(['enabled' => false]);
        GameGemAbility::query()->update(['enabled' => false]);

        $result = $this->gemService->generateGem($character, 1);

        $character->refresh();
        $this->assertSame(422, $result['status']);
        $this->assertSame('No enabled Gem abilities are available for Tier 1 crafting.', $result['message']);
        $this->assertFalse($result['craft_succeeded']);
        $this->assertNull($result['crafted_gem']);
        $this->assertNull($result['crafted_gem_preview']);
        $this->assertSame($startingGoldDust, $character->gold_dust);
        $this->assertSame($startingShards, $character->shards);
        $this->assertSame($startingCopper, $character->copper_coins);
    }

    public function test_cannot_craft_when_gem_bag_is_full()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
            'gem_bag_limit' => 2,
        ]);

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 1,
        ]);

        GemBagSlot::create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 1,
        ]);

        $result = $this->gemService->generateGem($character, 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your Gem Bag is full. Use or remove gems before crafting more.', $result['message']);
    }

    public function test_cannot_craft_when_skill_level_required_to_high()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $result = $this->gemService->generateGem($character, 4);

        $this->assertEquals(200, $result['status']);
        $this->assertFalse($result['craft_succeeded']);
        $this->assertSame('This gem tier is too hard. You lost your investment and start to cry.', $result['message']);
    }

    public function test_gem_tier_chances_produce_expected_dcs()
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

    public function test_high_skill_bonus_guarantees_gem_crafting_success()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->skills()->where('game_skill_id', $this->gemSkill->id)->update([
            'level' => 76,
        ]);

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('passesPercentage')->once()->with(100.0)->andReturn(true);
        });

        $result = (new GemServiceFactory)->build($chanceCalculator)->generateGem($character->refresh(), 4);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
    }

    public function test_low_roll_succeeds_when_effective_chance_is_one()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->skills()->where('game_skill_id', $this->gemSkill->id)->update([
            'level' => 26,
        ]);

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('passesPercentage')->once()->with(100.0)->andReturn(true);
        });

        $result = (new GemServiceFactory)->build($chanceCalculator)->generateGem($character->refresh(), 1);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
    }

    public function test_gem_crafting_can_still_fail_when_roll_exceeds_effective_chance()
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
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('passesPercentage')->once()->with(25.0)->andReturn(false);
        });

        $result = (new GemServiceFactory)->build($chanceCalculator)->generateGem($character->refresh(), 4);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You failed to craft the gem, the item explodes before you into a pile of wasted effort and time.';
        });
    }

    public function test_failed_gem_craft_still_charges_the_tier_cost()
    {
        Event::fake();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('passesPercentage')->once()->andReturn(false);
        });

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $result = (new GemServiceFactory)->build($chanceCalculator)->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertLessThan(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(CurrencyLimit::MAX_COPPER, $character->copper_coins);
        $this->assertLessThan(CurrencyLimit::MAX_SHARDS, $character->shards);
    }

    public function test_craft_the_gem()
    {
        Event::fake();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('passesPercentage')->once()->andReturn(true);
        });

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $result = (new GemServiceFactory)->build($chanceCalculator)->generateGem($character, 1);

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
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $result = $this->gemService->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
        $this->assertEquals(200, $result['status']);

        Event::assertNotDispatched(UpdateSkillEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_crafting_the_same_gem_twice_creates_two_separate_slots()
    {
        Event::fake();

        $gem = $this->createGem(['name' => 'Sample', 'tier' => 1]);

        $gemBuilder = Mockery::mock(GemBuilder::class, function (MockInterface $mock) use ($gem) {
            $mock->shouldReceive('canBuildTier')->once()->with(1)->andReturn(true);
            $mock->shouldReceive('buildGem')->once()->andReturn($gem);
        });

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('passesPercentage')->once()->andReturn(true);
        });

        $character = $this->character->getCharacter();

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gem->id,
            'amount' => 1,
        ]);

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $result = (new GemServiceFactory)->build($chanceCalculator, $gemBuilder)->generateGem($character, 1);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(2, $character->gemBag->gemSlots->count());
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
        $this->assertEquals(1, $character->gemBag->gemSlots->last()->amount);

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
