<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Factories;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingHandlerFactory;
use App\Game\Automation\BatchCrafting\Handlers\CraftAmountHandler;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BatchCraftingHandlerFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_resolves_the_registered_handler_through_the_container(): void
    {
        $factory = resolve(BatchCraftingHandlerFactory::class);

        $handler = $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT);

        $this->assertInstanceOf(CraftAmountHandler::class, $handler);
    }

    public function test_make_throws_clearly_when_the_registry_has_no_handler_for_the_type_and_mode(): void
    {
        $registry = new BatchCraftingAttributeRegistry([], []);
        $factory = new BatchCraftingHandlerFactory($registry, $this->app);

        $this->expectException(InvalidArgumentException::class);

        $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT);
    }

    public function test_make_throws_clearly_when_the_container_resolves_a_class_not_implementing_the_contract(): void
    {
        $registry = new BatchCraftingAttributeRegistry([], [CraftAmountHandler::class]);
        $factory = new BatchCraftingHandlerFactory($registry, $this->app);
        $this->app->bind(CraftAmountHandler::class, fn () => new class {});

        $this->expectException(InvalidArgumentException::class);

        $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT);
    }
}
