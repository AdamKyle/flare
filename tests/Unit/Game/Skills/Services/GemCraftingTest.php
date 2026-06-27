<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\GemBagSlot;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Values\GemTypeValue;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;

class GemCraftingTest extends TestCase
{
    use CreateGameSkill, CreateGem, RefreshDatabase;

    private ?CharacterFactory $character;

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->gemSkill = null;
    }

    public function test_gem_crafting_fails_when_gem_bag_is_full(): void
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'gem_bag_limit' => 0,
        ]);

        $result = resolve(GemService::class)->generateGem($character->refresh(), 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your Gem Bag is full. Use or remove gems before crafting more.', $result['message']);
    }

    public function test_gem_crafting_writes_to_gem_bag_slot(): void
    {
        Event::fake();

        $gemService = Mockery::mock(GemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $gemService->__construct(resolve(GemBuilder::class));

        $this->instance(GemService::class, $gemService);

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $result = resolve(GemService::class)->generateGem($character->refresh(), 1);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
    }

    public function test_gem_crafting_creates_separate_slot_when_same_gem_exists(): void
    {
        Event::fake();

        $gem = $this->createGem([
            'name' => 'TestGem',
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

        $gemService = Mockery::mock(GemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
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

        $result = $gemService->generateGem($character->refresh(), 1);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(2, $character->gemBag->gemSlots->count());
        $this->assertEquals(1, $character->gemBag->gemSlots->first()->amount);
        $this->assertEquals(1, $character->gemBag->gemSlots->last()->amount);
    }

    public function test_gem_crafting_succeeds_when_current_count_plus_one_equals_limit(): void
    {
        Event::fake();

        $gemService = Mockery::mock(GemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $gemService->__construct(resolve(GemBuilder::class));

        $this->instance(GemService::class, $gemService);

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'gem_bag_limit' => 5,
        ]);

        GemBagSlot::create([
            'gem_bag_id' => $character->refresh()->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 4,
        ]);

        $result = resolve(GemService::class)->generateGem($character->refresh(), 1);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(5, $character->refresh()->getGemBagCount());
    }

    public function test_gem_crafting_fails_when_current_count_plus_one_exceeds_limit(): void
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'gem_bag_limit' => 5,
        ]);

        GemBagSlot::create([
            'gem_bag_id' => $character->refresh()->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 5,
        ]);

        $result = resolve(GemService::class)->generateGem($character->refresh(), 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your Gem Bag is full. Use or remove gems before crafting more.', $result['message']);
    }

    public function test_gem_crafting_fails_when_stacking_existing_row_would_exceed_limit(): void
    {
        $gem = $this->createGem([
            'name' => 'StackTestGem',
            'tier' => 1,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::ICE,
            'primary_atonement_amount' => 0.10,
            'secondary_atonement_amount' => 0.10,
            'tertiary_atonement_amount' => 0.10,
        ]);

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'gem_bag_limit' => 5,
        ]);

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gem->id,
            'amount' => 5,
        ]);

        $result = resolve(GemService::class)->generateGem($character->refresh(), 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your Gem Bag is full. Use or remove gems before crafting more.', $result['message']);
        $this->assertEquals(5, $character->refresh()->gemBag->gemSlots->first()->amount);
    }
}
