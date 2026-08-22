<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Orchestrators;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Orchestrators\AlchemyOrchestrator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class AlchemyOrchestratorTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    public function test_orchestrate_resolves_and_executes_the_alchemy_amount_handler(): void
    {
        $item = $this->createItem([
            'gold_dust_cost' => 10,
            'shards_cost' => 5,
            'skill_level_required' => 1,
            'skill_level_trivial' => 0,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST, 'shards' => CurrencyLimit::MAX_SHARDS]);
        $character = $character->refresh();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $item->id, 'alchemy_amount' => 5, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = resolve(AlchemyOrchestrator::class)->orchestrate($batchCrafting, $character);

        $this->assertTrue($result->didCraft());
    }
}
