<?php

namespace Tests\Unit\Game\Automation\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\GameSkill;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Values\AutomatedCraftingAttemptTracker;
use App\Game\Automation\Values\AutomatedCraftingResult;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class AutomatedCraftingHandlerTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?AutomatedCraftingHandler $handler = null;

    private ?CharacterFactory $characterFactory = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomationCraftingLogger $craftingLogger = null;

    private ?CraftingService $craftingService = null;

    private ?ShopService $shopService = null;

    private ?GameSkill $weaponCraftingSkill = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->weaponCraftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]);

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($this->weaponCraftingSkill)
            ->givePlayerLocation();

        $this->character = $this->characterFactory->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->craftingLogger = resolve(FactionLoyaltyAutomationCraftingLogger::class)->setUp($this->factionLoyaltyAutomation);
        $this->craftingService = resolve(CraftingService::class);
        $this->shopService = resolve(ShopService::class);

        $this->handler = new AutomatedCraftingHandler(
            $this->craftingService,
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );
    }

    public function tearDown(): void
    {
        $this->handler = null;
        $this->characterFactory = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyAutomation = null;
        $this->factionLoyaltyNpc = null;
        $this->craftingLogger = null;
        $this->craftingService = null;
        $this->shopService = null;
        $this->weaponCraftingSkill = null;

        parent::tearDown();
    }

    public function testHandleReturnsItemNotFoundWhenTargetItemDoesNotExist(): void
    {
        Event::fake();

        $result = $this->handler
            ->setUp($this->character, 999999, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::ITEM_NOT_FOUND, $result->getResultType());
        $this->assertEquals(999999, $result->getTargetItemId());
    }

    public function testHandleReturnsNoCraftingSkillWhenCharacterDoesNotHaveRequiredCraftingSkill(): void
    {
        Event::fake();

        $targetItem = $this->createItem([
            'type' => 'body',
            'crafting_type' => 'armour',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $result = $this->handler
            ->setUp($this->character, $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::NO_CRAFTING_SKILL, $result->getResultType());
        $this->assertEquals('armour', $result->getCraftingType());
    }

    public function testHandleReturnsNotEnoughGoldWhenCharacterCannotAffordTargetItem(): void
    {
        Event::fake();

        $targetItem = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 0,
        ]);

        $result = $this->handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $result->getResultType());
        $this->assertEquals($targetItem->id, $result->getCraftedItemId());
    }

    public function testHandleCraftsTargetItemWhenCraftingRollSucceeds(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
            })
        );

        $targetItem = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 1000,
        ]);

        $handler = new AutomatedCraftingHandler(
            $this->app->make(CraftingService::class),
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );

        $result = $handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $result->getResultType());
        $this->assertEquals($targetItem->id, $result->getCraftedItemId());
        $this->assertTrue($result->hasCraftedTargetItem());
        $this->assertEquals(1, $result->getSuccessfulTargetCrafts());
        $this->assertEquals(10, $result->getGoldSpent());
    }

    public function testHandleReturnsMaxAttemptsReachedWhenTargetCraftingFailsTooManyTimes(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
            })
        );

        $targetItem = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 1000,
        ]);

        $handler = new AutomatedCraftingHandler(
            $this->app->make(CraftingService::class),
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );

        $result = $handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->setMaxAttempts(1)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED, $result->getResultType());
        $this->assertEquals($targetItem->id, $result->getCraftedItemId());
        $this->assertEquals(1, $result->getAttempts());
        $this->assertEquals(1, $result->getFailedRolls());
    }

    public function testHandleReturnsNoTrainingItemWhenCharacterIsBelowTargetLevelAndNoTrainingItemExists(): void
    {
        Event::fake();

        $alchemyCraftingSkill = $this->createGameSkill([
            'name' => 'Alchemy Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]);

        $this->characterFactory->assignSkill($alchemyCraftingSkill);
        $this->character = $this->characterFactory->getCharacter();

        $targetItem = $this->createItem([
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 10,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $result = $this->handler
            ->setUp($this->character, $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::NO_TRAINING_ITEM, $result->getResultType());
        $this->assertTrue($result->hasStartedBelowTargetLevel());
    }

    public function testHandleReturnsNotEnoughGoldWhenCharacterCannotAffordTrainingItem(): void
    {
        Event::fake();

        $targetItem = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 10,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 0,
        ]);

        $result = $this->handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $result->getResultType());
        $this->assertTrue($result->hasStartedBelowTargetLevel());
    }

    public function testHandleReturnsMaxAttemptsReachedWhenTrainingCraftingFailsTooManyTimes(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
            })
        );

        $targetItem = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 10,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 1000,
        ]);

        $handler = new AutomatedCraftingHandler(
            $this->app->make(CraftingService::class),
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );

        $result = $handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->setMaxAttempts(1)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED, $result->getResultType());
        $this->assertEquals(1, $result->getAttempts());
        $this->assertEquals(1, $result->getFailedRolls());
    }

    public function testHandleCraftsMinimumTrainingItemsWhenCharacterStartsBelowTargetLevel(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('characterRoll')->times(50)->andReturn(100);
                $mock->shouldReceive('getDCCheck')->times(50)->andReturn(1);
            })
        );

        $targetItem = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 10,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 1000,
        ]);

        $handler = new AutomatedCraftingHandler(
            $this->app->make(CraftingService::class),
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );

        $result = $handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM, $result->getResultType());
        $this->assertEquals(50, $result->getAttempts());
        $this->assertEquals(50, $result->getSuccessfulTrainingCrafts());
        $this->assertTrue($result->hasStartedBelowTargetLevel());
    }

    public function testHandleCraftsTargetItemForFactionLoyaltyNpcWhenNpcTaskIsIncomplete(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
            })
        );

        $targetItem = $this->factionLoyaltyFactory->getCraftingItemsForNpc($this->factionLoyaltyNpc)[0];

        $this->character->update([
            'gold' => 1000,
        ]);

        $handler = new AutomatedCraftingHandler(
            $this->app->make(CraftingService::class),
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );

        $result = $handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->setCraftForNpc()
            ->setFactionLoyaltyNpc($this->factionLoyaltyNpc)
            ->handle();

        $fameTask = collect($this->factionLoyaltyNpc->refresh()->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['item_id'] ?? null) === $targetItem->id);

        $this->assertEquals(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $result->getResultType());
        $this->assertEquals(1, $fameTask['current_amount']);
        $this->assertTrue($result->hasCraftedTargetItem());
    }

    public function testHandleCraftsTargetItemForEventWhenCraftingRollSucceeds(): void
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
            })
        );

        $spellCraftingSkill = $this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]);

        $this->characterFactory->assignSkill($spellCraftingSkill);
        $this->character = $this->characterFactory->getCharacter();

        $targetItem = $this->createItem([
            'type' => ItemType::SPELL_DAMAGE->value,
            'crafting_type' => 'spell',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 1000,
        ]);

        $handler = new AutomatedCraftingHandler(
            $this->app->make(CraftingService::class),
            $this->shopService,
            new AutomatedCraftingAttemptTracker,
            new AutomatedCraftingResult,
        );

        $result = $handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->setCraftForEvent()
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $result->getResultType());
        $this->assertEquals('spell', $result->getCraftingType());
        $this->assertTrue($result->hasCraftedTargetItem());
    }

    public function testHandleReturnsNotEnoughGoldForArmourTrainingItemWhenCharacterStartsBelowTargetLevel(): void
    {
        Event::fake();

        $armourCraftingSkill = $this->createGameSkill([
            'name' => 'Armour Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]);

        $this->characterFactory->assignSkill($armourCraftingSkill);
        $this->character = $this->characterFactory->getCharacter();

        $targetItem = $this->createItem([
            'type' => 'body',
            'crafting_type' => 'armour',
            'can_craft' => true,
            'cost' => 10,
            'skill_level_required' => 10,
            'skill_level_trivial' => 100,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $this->character->update([
            'gold' => 0,
        ]);

        $result = $this->handler
            ->setUp($this->character->refresh(), $targetItem->id, $this->craftingLogger)
            ->handle();

        $this->assertEquals(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $result->getResultType());
        $this->assertEquals('armour', $result->getCraftingType());
        $this->assertTrue($result->hasStartedBelowTargetLevel());
    }
}