<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\DisenchantManyService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DisenchantManyServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    private ?GameSkill $enchantingSkill = null;

    private ?GameSkill $disenchantingSkill = null;

    private ?DisenchantManyService $service = null;

    private $skillCheckServiceMock = null;

    private ?Manager $manager = null;

    private ?CharacterInventoryCountTransformer $inventoryCountTransformer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING->value,
        ]);

        $this->disenchantingSkill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING->value,
        ]);

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($this->disenchantingSkill)
            ->assignSkill($this->enchantingSkill)
            ->givePlayerLocation();

        $this->skillCheckServiceMock = Mockery::mock(SkillCheckService::class);
        $this->instance(SkillCheckService::class, $this->skillCheckServiceMock);

        $this->service = $this->app->make(DisenchantManyService::class);

        $this->manager = $this->app->make(Manager::class);
        $this->inventoryCountTransformer = $this->app->make(CharacterInventoryCountTransformer::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->enchantingSkill = null;
        $this->disenchantingSkill = null;
        $this->service = null;
        $this->skillCheckServiceMock = null;
        $this->manager = null;
        $this->inventoryCountTransformer = null;
    }

    public function test_returns_no_eligible_items_to_disenchant(): void
    {
        $character = $this->characterFactory->getCharacter();

        $result = $this->service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, []);

        $this->assertEquals('No eligible items to disenchant.', $result['message']);
        $this->assertEquals([], $result['disenchanted_item']);
        $this->assertEquals(200, $result['status']);
    }

    public function test_processes_only_included_ids_and_dispatches_skill_event_on_pass(): void
    {
        Event::fake();

        $itemIncluded = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix']),
        ]);

        $itemIgnored = $this->createItem([
            'type' => 'weapon',
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix']),
        ]);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($itemIncluded)
            ->giveItem($itemIgnored)
            ->getCharacter();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->once()->andReturn(1);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->once()->andReturn(100);

        $result = $this->service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, ['ids' => [$itemIncluded->id]]);

        $character = $character->refresh();

        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertEquals('passed', $result['disenchanted_item'][0]['status']);
        $this->assertGreaterThanOrEqual(2, $result['disenchanted_item'][0]['gold_dust']);
        $this->assertEquals(1, $character->inventory->slots->count());
        $this->assertEquals(200, $result['status']);

        Event::assertDispatchedTimes(UpdateSkillEvent::class, 1);
    }

    public function test_processes_exclude_ids_and_handles_failure_award_of_one(): void
    {
        Event::fake();

        $itemToFail = $this->createItem([
            'type' => 'weapon',
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix']),
        ]);

        $itemExcluded = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix']),
        ]);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($itemToFail)
            ->giveItem($itemExcluded)
            ->getCharacter();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->once()->andReturn(100);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->once()->andReturn(1);

        $result = $this->service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, ['exclude' => [$itemExcluded->id]]);

        $character = $character->refresh();

        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertEquals('failed', $result['disenchanted_item'][0]['status']);
        $this->assertEquals(1, $result['disenchanted_item'][0]['gold_dust']);
        $this->assertEquals(1, $character->inventory->slots->count());
        $this->assertEquals(200, $result['status']);

        Event::assertDispatchedTimes(UpdateSkillEvent::class, 0);
    }

    public function test_exclude_empty_array_processes_all_items(): void
    {
        Event::fake();

        $itemA = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix']),
        ]);

        $itemB = $this->createItem([
            'type' => 'weapon',
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix']),
        ]);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($itemA)
            ->giveItem($itemB)
            ->getCharacter();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->twice()->andReturn(1, 1);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->twice()->andReturn(100, 100);

        $result = $this->service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, ['exclude' => []]);

        $character = $character->refresh();

        $this->assertCount(2, $result['disenchanted_item']);
        $this->assertEquals(0, $character->inventory->slots->count());
        Event::assertDispatchedTimes(UpdateSkillEvent::class, 2);
    }

    public function test_gold_dust_capped_prevents_award_but_still_dispatches_skill_on_pass(): void
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix']),
        ]);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->once()->andReturn(1);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->once()->andReturn(100);

        $result = $this->service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, []);

        $character = $character->refresh();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(0, $result['disenchanted_item'][0]['gold_dust']);
        Event::assertDispatchedTimes(UpdateSkillEvent::class, 1);
    }

    public function test_fallback_when_no_disenchanting_skill_present(): void
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix']),
        ]);

        $characterNoSkill = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->once()->andReturn(1);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->once()->andReturn(100);

        $result = $this->service->disenchantMany($this->manager, $this->inventoryCountTransformer, $characterNoSkill, []);

        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertEquals('passed', $result['disenchanted_item'][0]['status']);
        Event::assertDispatchedTimes(UpdateSkillEvent::class, 1);
    }

    public function test_applies_interest_branch_when_passing(): void
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix']),
        ]);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->once()->andReturn(1);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->once()->andReturn(100);

        $service = Mockery::mock(DisenchantManyService::class, [$this->skillCheckServiceMock])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('passesInterest')->once()->andReturn(true);

        $result = $service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, []);

        $character = $character->refresh();

        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertEquals('passed', $result['disenchanted_item'][0]['status']);
        $this->assertGreaterThanOrEqual(2, $result['disenchanted_item'][0]['gold_dust']);
        $this->assertGreaterThanOrEqual(2, $character->gold_dust);

        Event::assertDispatchedTimes(UpdateSkillEvent::class, 1);
    }

    public function test_skips_interest_branch_when_passing(): void
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix']),
        ]);

        $character = $this->characterFactory->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->skillCheckServiceMock
            ->shouldReceive('getDCCheck')->once()->andReturn(1);
        $this->skillCheckServiceMock
            ->shouldReceive('characterRoll')->once()->andReturn(100);

        $service = Mockery::mock(DisenchantManyService::class, [$this->skillCheckServiceMock])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('passesInterest')->once()->andReturn(false);

        $result = $service->disenchantMany($this->manager, $this->inventoryCountTransformer, $character, []);

        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertEquals('passed', $result['disenchanted_item'][0]['status']);

        Event::assertDispatchedTimes(UpdateSkillEvent::class, 1);
    }
}
