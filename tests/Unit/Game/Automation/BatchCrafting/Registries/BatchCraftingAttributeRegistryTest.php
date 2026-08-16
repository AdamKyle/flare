<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Registries;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingType;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Handlers\CraftAmountHandler;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use InvalidArgumentException;
use Tests\TestCase;

class BatchCraftingAttributeRegistryTest extends TestCase
{
    public function test_orchestrator_for_resolves_the_registered_orchestrator_class(): void
    {
        $registry = new BatchCraftingAttributeRegistry([CraftingOrchestrator::class], []);

        $this->assertSame(CraftingOrchestrator::class, $registry->orchestratorFor(BatchCraftingType::CRAFT));
    }

    public function test_handler_for_resolves_the_registered_handler_class(): void
    {
        $registry = new BatchCraftingAttributeRegistry([], [CraftAmountHandler::class]);

        $this->assertSame(CraftAmountHandler::class, $registry->handlerFor(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT));
    }

    public function test_orchestrator_for_throws_clearly_when_no_orchestrator_is_registered_for_the_type(): void
    {
        $registry = new BatchCraftingAttributeRegistry([], []);

        $this->expectException(InvalidArgumentException::class);

        $registry->orchestratorFor(BatchCraftingType::CRAFT);
    }

    public function test_handler_for_throws_clearly_when_no_handler_is_registered_for_the_type_and_mode(): void
    {
        $registry = new BatchCraftingAttributeRegistry([], []);

        $this->expectException(InvalidArgumentException::class);

        $registry->handlerFor(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT);
    }

    public function test_constructor_throws_clearly_for_a_duplicate_orchestrator_type_registration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BatchCraftingAttributeRegistry([CraftingOrchestrator::class, CraftingOrchestrator::class], []);
    }

    public function test_constructor_throws_clearly_for_a_duplicate_handler_type_and_mode_registration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BatchCraftingAttributeRegistry([], [CraftAmountHandler::class, CraftAmountHandler::class]);
    }

    public function test_constructor_throws_clearly_when_an_orchestrator_candidate_is_missing_the_dispatch_attribute(): void
    {
        $candidate = new class implements BatchCraftingOrchestrator
        {
            public function orchestrate(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
            {
                return BatchCraftingOperationResult::failed();
            }
        };

        $this->expectException(InvalidArgumentException::class);

        new BatchCraftingAttributeRegistry([$candidate::class], []);
    }

    public function test_constructor_throws_clearly_when_an_orchestrator_candidate_does_not_implement_the_contract(): void
    {
        $candidate = new #[HandlesBatchCraftingType(BatchCraftingType::CRAFT)] class {};

        $this->expectException(InvalidArgumentException::class);

        new BatchCraftingAttributeRegistry([$candidate::class], []);
    }

    public function test_constructor_throws_clearly_when_a_handler_candidate_is_missing_the_dispatch_attribute(): void
    {
        $candidate = new class implements BatchCraftingHandler
        {
            public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
            {
                return BatchCraftingOperationResult::failed();
            }
        };

        $this->expectException(InvalidArgumentException::class);

        new BatchCraftingAttributeRegistry([], [$candidate::class]);
    }

    public function test_constructor_throws_clearly_when_a_handler_candidate_does_not_implement_the_contract(): void
    {
        $candidate = new #[HandlesBatchCraftingMode(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT)] class {};

        $this->expectException(InvalidArgumentException::class);

        new BatchCraftingAttributeRegistry([], [$candidate::class]);
    }
}
