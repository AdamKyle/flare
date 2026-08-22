<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Services\HolyOilPlanSimulator;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateHolyStack;
use Tests\Traits\CreateItem;

class HolyOilPlanSimulatorTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateHolyStack, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?HolyItemService $holyItemService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->holyItemService = resolve(HolyItemService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->holyItemService = null;
    }

    public function test_simulate_plans_the_exact_application_count_and_consumes_oil_quantity(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 3]);
        $target = (object) ['id' => $targetItem->id, 'item' => $targetItem];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$target]), collect([$oilSlot]));

        $this->assertSame(3, $plan['total_applications']);
        $this->assertSame(3, $plan['targets'][0]['planned_applications']);
        $this->assertSame(3, $plan['targets'][0]['resulting_holy_stacks']);
    }

    public function test_simulate_computes_the_gold_dust_cost_per_application_from_the_holy_item_service_formula(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $target = (object) ['id' => $targetItem->id, 'item' => $targetItem];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 3, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$target]), collect([$oilSlot]));

        $expectedCostPerApplication = $this->holyItemService->getCost($target->item, $oilSlot->item);
        $this->assertSame($expectedCostPerApplication * 2, $plan['total_gold_dust_cost']);
        $this->assertSame($expectedCostPerApplication * 2, $plan['targets'][0]['gold_dust_cost']);
    }

    public function test_simulate_reports_saturation_when_the_target_is_already_at_max_stacks(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $this->createHolyStacks(2, ['item_id' => $targetItem->id]);
        $target = (object) ['id' => $targetItem->id, 'item' => $targetItem->refresh()];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$target]), collect([$oilSlot]));

        $this->assertSame(0, $plan['targets'][0]['planned_applications']);
        $this->assertSame(0, $plan['total_gold_dust_cost']);
        $this->assertSame(2, $plan['targets'][0]['resulting_holy_stacks']);
    }

    public function test_simulate_stops_planning_a_target_once_oil_units_are_exhausted(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $target = (object) ['id' => $targetItem->id, 'item' => $targetItem];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 2,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$target]), collect([$oilSlot]));

        $this->assertSame(2, $plan['total_applications']);
        $this->assertSame(2, $plan['targets'][0]['planned_applications']);
        $this->assertSame(2, $plan['targets'][0]['resulting_holy_stacks']);
    }

    public function test_simulate_reports_zero_gold_dust_cost_when_no_oil_units_are_available(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $target = (object) ['id' => $targetItem->id, 'item' => $targetItem];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 0,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$target]), collect([$oilSlot]));

        $this->assertSame(0, $plan['total_applications']);
        $this->assertSame(0, $plan['total_gold_dust_cost']);
    }

    public function test_simulate_plans_multiple_targets_independently(): void
    {
        $firstTargetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $firstTarget = (object) ['id' => $firstTargetItem->id, 'item' => $firstTargetItem];
        $secondTargetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $secondTarget = (object) ['id' => $secondTargetItem->id, 'item' => $secondTargetItem];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$firstTarget, $secondTarget]), collect([$oilSlot]));

        $this->assertSame(3, $plan['total_applications']);
        $this->assertSame(1, $plan['targets'][0]['planned_applications']);
        $this->assertSame(2, $plan['targets'][1]['planned_applications']);
    }

    public function test_simulate_draws_oils_from_the_given_slot_order_exhausting_one_before_the_next(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 3]);
        $target = (object) ['id' => $targetItem->id, 'item' => $targetItem];
        $firstOilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 1,
        ])->load('item');
        $secondOilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate(
            $this->holyItemService,
            collect([$target]),
            new Collection([$firstOilSlot, $secondOilSlot])
        );

        $this->assertSame(3, $plan['targets'][0]['planned_applications']);
    }

    public function test_simulate_marks_an_ineligible_target_type_with_zero_capacity(): void
    {
        $trinket = $this->createItem(['type' => 'trinket', 'holy_stacks' => 5]);
        $target = (object) ['id' => $trinket->id, 'item' => $trinket];
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->load('item');

        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, collect([$target]), collect([$oilSlot]));

        $this->assertFalse($plan['targets'][0]['eligible']);
        $this->assertSame(0, $plan['targets'][0]['planned_applications']);
    }
}
