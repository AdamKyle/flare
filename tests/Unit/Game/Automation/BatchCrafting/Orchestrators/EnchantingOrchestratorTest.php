<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Orchestrators;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Orchestrators\EnchantingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;

class EnchantingOrchestratorTest extends TestCase
{
    use CreateBatchCrafting, RefreshDatabase;

    public function test_orchestrate_resolves_and_executes_event_enchant_handler(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => 'event',
                'event_goal_id' => null,
                'event_enchant_phase' => 'enchant_event_inventory',
                'current_item_id' => null,
                'current_item_name' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'enchanting_xp_gained' => 0,
                'crafting_xp_gained' => 0,
                'fallback_cycle_position' => 0,
            ],
        ]);

        $result = resolve(EnchantingOrchestrator::class)->orchestrate($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_NOT_RUNNING, $result->endReason());
    }
}
