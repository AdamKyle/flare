<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Factories;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingOrchestratorFactory;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BatchCraftingOrchestratorFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_resolves_the_registered_orchestrator_through_the_container(): void
    {
        $factory = resolve(BatchCraftingOrchestratorFactory::class);

        $orchestrator = $factory->make(BatchCraftingType::CRAFT);

        $this->assertInstanceOf(CraftingOrchestrator::class, $orchestrator);
    }

    public function test_make_throws_clearly_when_the_registry_has_no_orchestrator_for_the_type(): void
    {
        $registry = new BatchCraftingAttributeRegistry([], []);
        $factory = new BatchCraftingOrchestratorFactory($registry, $this->app);

        $this->expectException(InvalidArgumentException::class);

        $factory->make(BatchCraftingType::CRAFT);
    }

    public function test_make_throws_clearly_when_the_container_resolves_a_class_not_implementing_the_contract(): void
    {
        $registry = new BatchCraftingAttributeRegistry([CraftingOrchestrator::class], []);
        $factory = new BatchCraftingOrchestratorFactory($registry, $this->app);
        $this->app->bind(CraftingOrchestrator::class, fn () => new class {});

        $this->expectException(InvalidArgumentException::class);

        $factory->make(BatchCraftingType::CRAFT);
    }
}
