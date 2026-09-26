<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Character\Values\CharacterClass;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Builders\AffixAttributeBuilder;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Services\GemComparison;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Npcs\Actions\QueenOfHearts\Services\RandomEnchantmentService;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Services\EnchantingAffixService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Transformers\EnchantingAffixTransformer;
use App\Game\Skills\Transformers\EnchantingItemTransformer;
use App\Game\Skills\Transformers\EventEnchantingItemTransformer;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalCraftingInventorySlot;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateScheduledEvent;

class EnchantingServiceTest extends TestCase
{
    use CreateClass,
        CreateEvent,
        CreateGameMap,
        CreateGameSkill,
        CreateGlobalCraftingInventory,
        CreateGlobalCraftingInventorySlot,
        CreateGlobalEventGoal,
        CreateItem,
        CreateItemAffix,
        CreateScheduledEvent,
        RefreshDatabase;

    private ?CharacterFactory $character;

    private ?EnchantingService $enchantingService;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?GlobalEventGoalEligibilityService $globalEventGoalEligibilityService;

    private ?HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal;

    private ?RandomEnchantmentService $randomEnchantmentService;

    private ?Pagination $pagination;

    private ?EnchantingItemTransformer $enchantingItemTransformer;

    private ?EventEnchantingItemTransformer $eventEnchantingItemTransformer;

    private ?EnchantingAffixTransformer $enchantingAffixTransformer;

    private ?EnchantingAffixService $enchantingAffixService;

    private ?Item $itemToEnchant;

    private ?ItemAffix $suffix;

    private ?ItemAffix $prefix;

    private ?GameSkill $enchantingSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING->value,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->enchantingSkill
        )->givePlayerLocation();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->andReturnUsing(
            fn (int $minimum, int $maximum): int => intdiv($minimum + $maximum, 2)
        );

        $chanceCalculator = new ChanceCalculator($randomNumberGenerator);

        $this->characterStatBuilder = new CharacterStatBuilder(
            new DefenceBuilder(),
            new DamageBuilder(new ClassRanksWeaponMasteriesBuilder()),
            new HealingBuilder(new ClassRanksWeaponMasteriesBuilder()),
            new HolyBuilder(),
            new ReductionsBuilder(),
            new ElementalAtonement(
                new GemComparison(new CharacterGemsTransformer(), new PlainDataSerializer(), new Manager()),
                new ElementAttackData(),
            ),
            new CharacterAreaGemEffectService(new AreaGemEffectService(), new GemProgressionEffectService()),
        );

        $this->globalEventGoalEligibilityService = new GlobalEventGoalEligibilityService();

        $eventGoalsService = new EventGoalsService($this->globalEventGoalEligibilityService);
        $globalEventGoalProgressionService = new GlobalEventGoalProgressionService($eventGoalsService);
        $randomAffixGenerator = new RandomAffixGenerator(new AffixAttributeBuilder($randomNumberGenerator, $chanceCalculator));

        $this->handleUpdatingEnchantingGlobalEventGoal = new HandleUpdatingEnchantingGlobalEventGoal(
            $randomAffixGenerator,
            $eventGoalsService,
            $globalEventGoalProgressionService,
            $this->globalEventGoalEligibilityService,
        );

        $this->randomEnchantmentService = new RandomEnchantmentService($randomAffixGenerator, $chanceCalculator);

        $this->pagination = new Pagination(new Manager());

        $itemEnricherFactory = new ItemEnricherFactory(
            new EquippableEnricher(),
            new EquippableItemTransformer(),
            new UsableItemTransformer(),
            new QuestItemTransformer(),
            new PlainDataSerializer(),
            new Manager(),
        );
        $craftingItemPreviewTransformer = new CraftingItemPreviewTransformer($itemEnricherFactory);

        $this->enchantingItemTransformer = new EnchantingItemTransformer($craftingItemPreviewTransformer);
        $this->eventEnchantingItemTransformer = new EventEnchantingItemTransformer($craftingItemPreviewTransformer);
        $this->enchantingAffixTransformer = new EnchantingAffixTransformer();

        $this->enchantingAffixService = new EnchantingAffixService(
            $this->characterStatBuilder,
            $this->globalEventGoalEligibilityService,
        );

        $enchantItemService = new EnchantItemService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $this->handleUpdatingEnchantingGlobalEventGoal,
        );

        $this->enchantingService = new EnchantingService(
            $this->characterStatBuilder,
            $enchantItemService,
            $this->randomEnchantmentService,
            $this->globalEventGoalEligibilityService,
            $this->pagination,
            $this->enchantingItemTransformer,
            $this->eventEnchantingItemTransformer,
            $this->enchantingAffixTransformer,
            $this->enchantingAffixService,
        );

        $this->itemToEnchant = $this->createItem([
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $this->suffix = $this->createItemAffix([
            'type' => 'suffix',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 1000,
        ]);

        $this->prefix = $this->createItemAffix([
            'type' => 'prefix',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 1000,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->enchantingSkill = null;
        $this->enchantingService = null;
        $this->characterStatBuilder = null;
        $this->globalEventGoalEligibilityService = null;
        $this->handleUpdatingEnchantingGlobalEventGoal = null;
        $this->randomEnchantmentService = null;
        $this->pagination = null;
        $this->enchantingItemTransformer = null;
        $this->eventEnchantingItemTransformer = null;
        $this->enchantingAffixTransformer = null;
        $this->enchantingAffixService = null;
        $this->suffix = null;
        $this->itemToEnchant = null;
    }

    public function test_fetch_affixes_and_items_that_can_be_enchanted()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);
    }

    public function test_get_cost_of_item_affixes_as_zero_when_affixes_do_not_exist()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [10000, 100001], 560);

        $this->assertEquals(0, $result);
    }

    public function test_get_cost_of_item_affixes_as_zero_when_items_do_not_exist()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], 560);

        $this->assertEquals(0, $result);
    }

    public function test_get_cost_of_affixes_to_attach()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(2000, $result);
    }

    public function test_get_cost_of_affixes_to_attach_as_a_merchant()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(floor(2000 - 2000 * 0.15), $result);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get a 15% reduction on enchanting items (reduction applied to total price).';
        });
    }

    public function test_get_cost_when_item_has_affixes_attached()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ])->id);

        $this->assertEquals(4000, $result);
    }

    public function test_enchant_item_and_the_cost_is_deducted()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id, $this->suffix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
    }

    public function test_enchant_item_with_non_existant_affixes_but_still_reduce_the_characters_gold_as_punishment()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [10000, 1500],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
    }

    public function test_cannot_enchant_item_when_skill_level_required_is_to_high()
    {

        Event::fake();

        $this->prefix->update([
            'skill_level_required' => 1800,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === new ServerMessageBuilder()->buildWithAdditionalInformation(CraftingMessageTypes::TO_HARD_TO_CRAFT);
        });
    }

    public function test_enchant_not_enough_int()
    {

        Event::fake();

        $this->prefix->update([
            'skill_level_trivial' => 1,
            'int_required' => 10000,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === new ServerMessageBuilder()->buildWithAdditionalInformation(CraftingMessageTypes::INT_TO_LOW_ENCHANTING);
        });
    }

    public function test_enchant_when_to_easy()
    {

        Event::fake();

        $this->prefix->update([
            'skill_level_trivial' => -10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === new ServerMessageBuilder()->buildWithAdditionalInformation(CraftingMessageTypes::TO_EASY_TO_CRAFT);
        });
    }

    public function test_enchanting_succeeds()
    {

        Event::fake();

        $enchantItemService = Mockery::mock(EnchantItemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldReceive('attachAffix')->once()->andReturn(true);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $enchantingService = new EnchantingService(
            $this->characterStatBuilder,
            $enchantItemService,
            $this->randomEnchantmentService,
            $this->globalEventGoalEligibilityService,
            $this->pagination,
            $this->enchantingItemTransformer,
            $this->eventEnchantingItemTransformer,
            $this->enchantingAffixTransformer,
            $this->enchantingAffixService,
        );

        $slot = $character->inventory->slots->first();

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) use ($slot) {
            return $event->message === 'Applied enchantment: '.$this->prefix->name.' to: '.$slot->item->refresh()->affix_name;
        });
    }

    public function test_enchanting_succeeds_while_enchanting_global_event_is_running()
    {

        Event::fake();

        $skillCheckService = Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
            $mock->shouldReceive('characterRoll')->once()->andReturn(100);
        });

        $enchantingService = new EnchantingService(
            $this->characterStatBuilder,
            new EnchantItemService($skillCheckService, $this->handleUpdatingEnchantingGlobalEventGoal),
            $this->randomEnchantmentService,
            $this->globalEventGoalEligibilityService,
            $this->pagination,
            $this->enchantingItemTransformer,
            $this->eventEnchantingItemTransformer,
            $this->enchantingAffixTransformer,
            $this->enchantingAffixService,
        );

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => true,
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        $this->assertNotNull($character->globalEventEnchants);

        $globalEventParticipation = GlobalEventParticipation::where('character_id', $character->id)->first();

        $this->assertNotNull($globalEventParticipation);

        $this->assertEmpty($character->inventory->slots);
    }

    public function test_enchanting_fails()
    {

        Event::fake();

        $enchantItemService = Mockery::mock(EnchantItemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldReceive('attachAffix')->once()->andReturn(false);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $enchantingService = new EnchantingService(
            $this->characterStatBuilder,
            $enchantItemService,
            $this->randomEnchantmentService,
            $this->globalEventGoalEligibilityService,
            $this->pagination,
            $this->enchantingItemTransformer,
            $this->eventEnchantingItemTransformer,
            $this->enchantingAffixTransformer,
            $this->enchantingAffixService,
        );

        $slot = $character->inventory->slots->first();

        $itemName = $slot->item->affix_name;

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) use ($itemName) {
            return $event->message === 'You failed to apply '.$this->prefix->name.' to: '.$itemName.'. The item shatters before you. You lost the investment.';
        });
    }

    public function test_failed_enchant_on_global_event_crafting_inventory_slot_does_not_dispatch_inventory_count_event_or_throw_type_error()
    {
        Event::fake();

        $enchantItemService = Mockery::mock(EnchantItemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldReceive('attachAffix')->once()->andReturn(false);
        });

        $character = $this->character->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $goal = $this->createGlobalEventGoal();
        $inventory = $this->createGlobalCraftingInventory(['character_id' => $character->id, 'global_event_goal_id' => $goal->id]);
        $slot = $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $this->itemToEnchant->id]);

        $enchantingService = new EnchantingService(
            $this->characterStatBuilder,
            $enchantItemService,
            $this->randomEnchantmentService,
            $this->globalEventGoalEligibilityService,
            $this->pagination,
            $this->enchantingItemTransformer,
            $this->eventEnchantingItemTransformer,
            $this->enchantingAffixTransformer,
            $this->enchantingAffixService,
        );

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $slot, 1000);

        Event::assertNotDispatched(UpdateCharacterInventoryCountEvent::class);
        $this->assertNull(GlobalEventCraftingInventorySlot::find($slot->id));
    }

    public function test_failed_enchant_on_normal_inventory_slot_still_dispatches_inventory_count_event()
    {
        Event::fake();

        $enchantItemService = Mockery::mock(EnchantItemService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldReceive('attachAffix')->once()->andReturn(false);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $enchantingService = new EnchantingService(
            $this->characterStatBuilder,
            $enchantItemService,
            $this->randomEnchantmentService,
            $this->globalEventGoalEligibilityService,
            $this->pagination,
            $this->enchantingItemTransformer,
            $this->eventEnchantingItemTransformer,
            $this->enchantingAffixTransformer,
            $this->enchantingAffixService,
        );

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $slot, 1000);

        Event::assertDispatched(UpdateCharacterInventoryCountEvent::class);
    }

    public function test_get_time_addition_for_enchanting_should_be_triple()
    {
        $item = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ]);

        $time = $this->enchantingService->timeForEnchanting($item);

        $this->assertEquals('triple', $time);
    }

    public function test_get_time_addition_for_enchanting_should_be_double()
    {
        $item = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
        ]);

        $time = $this->enchantingService->timeForEnchanting($item);

        $this->assertEquals('double', $time);
    }

    public function test_get_time_addition_for_enchanting_should_be_null()
    {
        $time = $this->enchantingService->timeForEnchanting($this->itemToEnchant);

        $this->assertNull($time);
    }

    public function test_get_inventory_slot_from_slot_id()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $slotId = $character->inventory->slots->first()->id;

        $slot = $this->enchantingService->getSlotFromInventory($character, $slotId);

        $this->assertNotNull($slot);
        $this->assertEquals($slotId, $slot->id);
    }

    public function test_fetch_character_enchanting_xp()
    {
        $character = $this->character->getCharacter();

        $weaponCraftingXpData = $this->enchantingService->getEnchantingXP($character);

        $enchantingSkill = $character->skills()->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->assertEquals($weaponCraftingXpData['skill_name'], $enchantingSkill->baseSkill->name);
    }

    public function test_get_item_for_global_event()
    {

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $globalEventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character = $this->character->getCharacter();

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character = $character->refresh();

        $inventory = $this->createGlobalCraftingInventory([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
        ]);

        $this->assertNotNull($inventory->id);

        $slot = $this->createGlobalCraftingInventorySlot([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $this->createItem()->id,
        ]);

        $foundSlot = $this->enchantingService->getSlotFromInventory($character, $slot->id);

        $this->assertEquals($foundSlot->id, $slot->id);
    }

    public function test_enchant_item_for_batch_validates_all_affixes_before_charging_gold(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);
        $goldBefore = (int) $character->gold;

        $result = $this->enchantingService->enchantItemForBatch($character, $this->itemToEnchant, [$this->prefix->id, 999999], 1000);

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_affix', $result['reason']);
        $this->assertSame($goldBefore, (int) $character->refresh()->gold);
    }

    public function test_enchant_item_for_batch_does_not_charge_gold_when_affix_validation_fails(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);
        $wrongTypeAffix = $this->createItemAffix([
            'type' => 'invalid',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 1000,
        ]);
        $goldBefore = (int) $character->gold;

        $result = $this->enchantingService->enchantItemForBatch($character, $this->itemToEnchant, [$wrongTypeAffix->id], 1000);

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_affix_type', $result['reason']);
        $this->assertSame($goldBefore, (int) $character->refresh()->gold);
    }

    public function test_enchant_item_for_batch_rejects_affix_above_character_int(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000, 'int' => 1]);
        $affix = $this->createItemAffix([
            'type' => 'prefix',
            'int_required' => 10000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 1000,
        ]);
        $goldBefore = (int) $character->gold;

        $result = $this->enchantingService->enchantItemForBatch($character, $this->itemToEnchant, [$affix->id], 1000);

        $this->assertFalse($result['success']);
        $this->assertSame('int_too_low', $result['reason']);
        $this->assertSame($goldBefore, (int) $character->refresh()->gold);
    }

    public function test_enchant_item_for_batch_rejects_affix_above_enchanting_skill(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);
        $affix = $this->createItemAffix([
            'type' => 'prefix',
            'int_required' => 1,
            'skill_level_required' => 10000,
            'skill_level_trivial' => 10001,
            'cost' => 1000,
        ]);
        $goldBefore = (int) $character->gold;

        $result = $this->enchantingService->enchantItemForBatch($character, $this->itemToEnchant, [$affix->id], 1000);

        $this->assertFalse($result['success']);
        $this->assertSame('skill_too_low', $result['reason']);
        $this->assertSame($goldBefore, (int) $character->refresh()->gold);
    }

    public function test_resolve_batch_affixes_returns_both_requested_affixes(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->resolveBatchAffixes($character, $this->prefix->id, $this->suffix->id);

        $this->assertSame($this->prefix->id, $result['prefix']->id);
        $this->assertSame($this->suffix->id, $result['suffix']->id);
        $this->assertNull($result['error']);
    }

    public function test_resolve_batch_affixes_requires_at_least_one_affix(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->resolveBatchAffixes($character, null, null);

        $this->assertSame('no_affixes', $result['error']);
        $this->assertNull($result['prefix']);
        $this->assertNull($result['suffix']);
    }

    public function test_resolve_batch_affixes_rejects_unknown_prefix_id(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->resolveBatchAffixes($character, 999999, null);

        $this->assertSame('invalid_prefix', $result['error']);
    }

    public function test_resolve_batch_affixes_rejects_affix_above_skill_level(): void
    {
        $this->prefix->update(['skill_level_required' => 10000]);
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->resolveBatchAffixes($character, $this->prefix->id, null);

        $this->assertSame('invalid_prefix', $result['error']);
    }

    public function test_resolve_batch_affixes_never_substitutes_a_different_suffix(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->resolveBatchAffixes($character, null, 999999);

        $this->assertSame('invalid_suffix', $result['error']);
        $this->assertNull($result['prefix']);
    }

    public function test_find_meaningful_batch_affixes_returns_the_strongest_non_trivial_affix(): void
    {
        $weakerPrefix = $this->createItemAffix([
            'type' => 'prefix',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 500,
        ]);

        $character = $this->character->getCharacter();

        $result = $this->enchantingService->findMeaningfulBatchAffixes($character);

        $this->assertSame($this->prefix->id, $result['prefix']->id);
        $this->assertNotEquals($weakerPrefix->id, $result['prefix']->id);
        $this->assertFalse($result['intelligence_blocked']);
    }

    public function test_find_meaningful_batch_affixes_reports_intelligence_blocked_without_choosing_a_weaker_affix(): void
    {
        $this->prefix->update(['int_required' => 10000]);
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->findMeaningfulBatchAffixes($character);

        $this->assertSame($this->prefix->id, $result['prefix']->id);
        $this->assertTrue($result['intelligence_blocked']);
    }

    public function test_find_meaningful_batch_affixes_returns_null_when_none_are_meaningful(): void
    {
        $this->prefix->update(['skill_level_trivial' => -10]);
        $this->suffix->update(['skill_level_trivial' => -10]);
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->findMeaningfulBatchAffixes($character);

        $this->assertNull($result['prefix']);
        $this->assertNull($result['suffix']);
        $this->assertFalse($result['intelligence_blocked']);
    }

    public function test_find_event_batch_affixes_returns_the_cheapest_affordable_affix(): void
    {
        $cheaperPrefix = $this->createItemAffix([
            'type' => 'prefix',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 500,
        ]);

        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);

        $result = $this->enchantingService->findEventBatchAffixes($character->refresh());

        $this->assertSame($cheaperPrefix->id, $result['prefix']->id);
        $this->assertFalse($result['intelligence_blocked']);
    }

    public function test_find_event_batch_affixes_excludes_affixes_the_character_cannot_afford(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);

        $result = $this->enchantingService->findEventBatchAffixes($character->refresh());

        $this->assertNull($result['prefix']);
        $this->assertNull($result['suffix']);
    }

    public function test_find_event_batch_affixes_reports_intelligence_blocked_without_choosing_a_weaker_affix(): void
    {
        $this->prefix->update(['int_required' => 10000]);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);

        $result = $this->enchantingService->findEventBatchAffixes($character->refresh());

        $this->assertSame($this->prefix->id, $result['prefix']->id);
        $this->assertTrue($result['intelligence_blocked']);
    }

    public function test_find_enchanting_skill_returns_the_characters_enchanting_skill(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->findEnchantingSkill($character);

        $this->assertNotNull($result);
        $this->assertSame($this->enchantingSkill->id, $result->game_skill_id);
    }

    public function test_find_enchanting_skill_returns_null_when_no_enchanting_game_skill_exists(): void
    {
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->enchantingSkill->id)->delete();
        GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->delete();

        $result = $this->enchantingService->findEnchantingSkill($character);

        $this->assertNull($result);
    }
}
