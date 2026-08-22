<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Factories;

use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingHandlerFactory;
use App\Game\Automation\BatchCrafting\Handlers\AlchemyAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\AlchemyExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantSetHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftEventHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftSetHandler;
use App\Game\Automation\BatchCrafting\Handlers\EventEnchantHandler;
use App\Game\Automation\BatchCrafting\Handlers\HolyOilSelectedItemsHandler;
use App\Game\Automation\BatchCrafting\Handlers\HolyOilSetHandler;
use App\Game\Automation\BatchCrafting\Handlers\TrinketryHandler;
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

    public function test_make_resolves_the_correct_handler_for_every_final_type_and_mode(): void
    {
        $factory = resolve(BatchCraftingHandlerFactory::class);

        $this->assertInstanceOf(CraftAmountHandler::class, $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT));
        $this->assertInstanceOf(CraftExperienceHandler::class, $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::EXPERIENCE));
        $this->assertInstanceOf(CraftSetHandler::class, $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::SET));
        $this->assertInstanceOf(CraftEventHandler::class, $factory->make(BatchCraftingType::CRAFT, CraftingBatchMode::EVENT));
        $this->assertInstanceOf(CraftAndEnchantAmountHandler::class, $factory->make(BatchCraftingType::CRAFT_AND_ENCHANT, CraftAndEnchantBatchMode::AMOUNT));
        $this->assertInstanceOf(CraftAndEnchantExperienceHandler::class, $factory->make(BatchCraftingType::CRAFT_AND_ENCHANT, CraftAndEnchantBatchMode::EXPERIENCE));
        $this->assertInstanceOf(CraftAndEnchantSetHandler::class, $factory->make(BatchCraftingType::CRAFT_AND_ENCHANT, CraftAndEnchantBatchMode::SET));
        $this->assertInstanceOf(EventEnchantHandler::class, $factory->make(BatchCraftingType::ENCHANT, EnchantingBatchMode::EVENT));
        $this->assertInstanceOf(AlchemyAmountHandler::class, $factory->make(BatchCraftingType::ALCHEMY, AlchemyBatchMode::AMOUNT));
        $this->assertInstanceOf(AlchemyExperienceHandler::class, $factory->make(BatchCraftingType::ALCHEMY, AlchemyBatchMode::EXPERIENCE));
        $this->assertInstanceOf(HolyOilSelectedItemsHandler::class, $factory->make(BatchCraftingType::HOLY_OILS, HolyOilsBatchMode::SELECTED_ITEMS));
        $this->assertInstanceOf(HolyOilSetHandler::class, $factory->make(BatchCraftingType::HOLY_OILS, HolyOilsBatchMode::INVENTORY_SET));
        $this->assertInstanceOf(TrinketryHandler::class, $factory->make(BatchCraftingType::TRINKETRY, TrinketryBatchMode::EXPERIENCE));
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
