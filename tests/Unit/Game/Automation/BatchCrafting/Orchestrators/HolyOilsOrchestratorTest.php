<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Orchestrators;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\HolyOilTargetKind;
use App\Game\Automation\BatchCrafting\Orchestrators\HolyOilsOrchestrator;
use App\Game\Automation\BatchCrafting\Values\HolyOilApplicationPlanEntry;
use App\Game\Core\Currency\Services\CurrencyLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class HolyOilsOrchestratorTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateItem, RefreshDatabase;

    public function test_orchestrate_resolves_and_executes_the_selected_items_handler(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $character = $characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = resolve(HolyOilsOrchestrator::class)->orchestrate($batchCrafting, $character);

        $this->assertTrue($result->didCraft());
    }
}
