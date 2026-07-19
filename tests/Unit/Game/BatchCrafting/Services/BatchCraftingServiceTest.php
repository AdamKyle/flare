<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\HolyStack;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Services\BatchCraftingLogger;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ItemSpecialtyType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalCraftingInventorySlot;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateScheduledEvent;
use Tests\Traits\CreateUser;

class BatchCraftingServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacter, CreateCharacterAutomation, CreateCharacterBoon, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalCraftingInventory, CreateGlobalCraftingInventorySlot, CreateGlobalEventGoal, CreateInventorySets, CreateInventorySlot, CreateItem, CreateItemAffix, CreateScheduledEvent, CreateUser, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testStopOnDeath(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'is_dead' => true]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::DIED->value, $result->ended_reason);
    }

    public function testStopOnNoGold(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 0]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::CRAFT->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD->value, $result->ended_reason);
    }

    public function testStopOnNoGoldDust(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 0]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::ALCHEMY->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST->value, $result->ended_reason);
    }

    public function testStopOnNoShards(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 0]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::TRINKETRY->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_SHARDS->value, $result->ended_reason);
    }

    public function testStopOnNoRequiredCurrency(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'progress' => ['required_currency' => 'copper_coins']]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_REQUIRED_CURRENCY->value, $result->ended_reason);
    }

    public function testCraftExperienceDoesNotStopOnFullNormalInventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 0, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
        $this->assertSame(0, $character->refresh()->getInventoryCount());
    }

    public function testAlchemyBatchDoesNotStopOnFullNormalInventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10, 'inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Full Inventory Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testStopOnMaxedCraftingLevelsOrNothingLeftToCraft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'progress' => ['nothing_left' => true]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
    }

    public function testMaxLevelCraftBatchStillCraftsWhenEligibleCraftableItemsExist(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Max Level Batch Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertGreaterThan(0, $result->crafted_count);
    }

    public function testKeptCraftedItemsSetServerMessageIncludesLinkMetadata(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Link Metadata Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $setSlot = SetSlot::whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))
            ->where('item_id', $item->id)
            ->first();

        $this->assertNotNull($setSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($setSlot, $item) {
            return $event->message === 'Kept: ' . $item->name . ' in your Crafted Items Set.'
                && $event->id === $setSlot->id
                && $event->source === 'crafted_items_set'
                && $event->linkText === $item->name;
        });
    }

    public function testBatchCraftKeptOutputCraftedMessageIsLinkedToExactSetSlot(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Linked Craft Message Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $setSlot = SetSlot::whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))
            ->where('item_id', $item->id)
            ->first();

        $this->assertNotNull($setSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($setSlot, $item) {
            return $event->message === 'You crafted a: ' . $item->name . '!'
                && $event->id === $setSlot->id
                && $event->source === 'crafted_items_set'
                && $event->linkText === $item->name;
        });
    }

    public function testBatchCraftKeptOutputDoesNotDispatchDuplicatePlainCraftedMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'No Duplicate Craft Message Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedMessages = collect(Event::dispatched(ServerMessageEvent::class))
            ->filter(fn (array $dispatched) => $dispatched[0]->message === 'You crafted a: ' . $item->name . '!');

        $this->assertCount(1, $craftedMessages);
    }

    public function testBatchCraftAndEnchantKeptOutputEnchantMessageIsLinkedToExactSetSlot(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Linked Enchant Message Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Linked Enchant Message Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $setSlot = SetSlot::whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))
            ->whereHas('item', fn ($query) => $query->where('item_prefix_id', $prefix->id))
            ->first();

        $this->assertNotNull($setSlot);
        $expectedItemName = $setSlot->item->affix_name;
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($setSlot, $prefix, $expectedItemName) {
            return $event->message === 'Applied enchantment: ' . $prefix->name . ' to: ' . $expectedItemName
                && $event->id === $setSlot->id
                && $event->source === 'crafted_items_set'
                && $event->linkText === $expectedItemName;
        });
    }

    public function testBatchTrinketryKeptOutputCraftedMessageIsLinkedToExactSetSlot(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'copper_coins' => 1000000, 'inventory_max' => 30]);
        $trinket = $this->createItem(['name' => 'Linked Trinketry Message Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $setSlot = SetSlot::whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))
            ->where('item_id', $trinket->id)
            ->first();

        $this->assertNotNull($setSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($setSlot, $trinket) {
            return $event->message === 'You crafted a: ' . $trinket->name . '!'
                && $event->id === $setSlot->id
                && $event->source === 'crafted_items_set'
                && $event->linkText === $trinket->name;
        });
    }

    public function testBatchCraftSoldOutputDoesNotEmitLinkMetadata(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'No Fake Link Sold Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($item) {
            return str_starts_with($event->message, 'Sold: ' . $item->name)
                && is_null($event->id)
                && is_null($event->source)
                && is_null($event->linkText);
        });
    }

    public function testAlchemyAmountKeepDispatchesLinkedKeptInBagMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Linked Alchemy Amount Keep Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $alchemyBagSlot = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertNotNull($alchemyBagSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($alchemyBagSlot, $item) {
            return $event->message === 'Kept: ' . $item->name . ' in your Alchemy Bag.'
                && $event->id === $alchemyBagSlot->id
                && $event->source === 'alchemy_bag'
                && $event->linkText === $item->name;
        });
    }

    public function testAlchemyExperienceKeepDispatchesLinkedKeptInBagMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 1, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Linked Alchemy Experience Keep Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $alchemyBagSlot = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertNotNull($alchemyBagSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($alchemyBagSlot, $item) {
            return $event->message === 'Kept: ' . $item->name . ' in your Alchemy Bag.'
                && $event->id === $alchemyBagSlot->id
                && $event->source === 'alchemy_bag'
                && $event->linkText === $item->name;
        });
    }

    public function testAlchemyExperienceKeepBestDestroyRestDispatchesLinkedKeptMessageForRetainedBest(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $alchemySkill = $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value);
        $alchemySkill->update(['level' => 1, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'alchemy_bag_limit' => 20]);
        $item = $this->createItem(['name' => 'Linked Alchemy Keep Best Destroy Rest Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $alchemyBagSlot = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertNotNull($alchemyBagSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($alchemyBagSlot, $item) {
            return $event->message === 'Kept: ' . $item->name . ' in your Alchemy Bag.'
                && $event->id === $alchemyBagSlot->id
                && $event->source === 'alchemy_bag'
                && $event->linkText === $item->name;
        });
    }

    public function testAlchemyDestroyDoesNotEmitLinkMetadata(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'No Fake Link Alchemy Destroy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertNotDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return str_starts_with($event->message, 'Kept: ');
        });
    }

    public function testAlchemyListDoesNotEmitLinkMetadata(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'No Fake Link Alchemy List Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id, 'listing_price' => 5],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertNotDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return str_starts_with($event->message, 'Kept: ');
        });
    }

    public function testMaxLevelCraftAndEnchantBatchStillCraftsAndEnchantsWhenEligibleItemsAndAffixesExist(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Max Level Batch Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Max Level Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertGreaterThan(0, $result->crafted_count);
        $this->assertNotNull($result->action_log[1]['enchanted_item'] ?? null);
    }

    public function testCraftBatchProcessesOneExperienceItemPerTickAndPersistsQueueInProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['skipped_count' => 138],
            'actions' => [['action' => 'craft', 'status' => 'skipped', 'failure' => 'No craftable item found.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(138, $result->crafted_count + $result->failed_count + $result->skipped_count, $result->ended_reason ?? 'no end reason');
        $this->assertNull($result->ended_reason);
    }

    public function testCraftAndEnchantFirstTickCraftsAndSetsEnchantPhaseInProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 1],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'end_reason' => BatchCraftingEndReason::AMOUNT_REACHED,
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'craft_and_enchant', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(1, $result->crafted_count, $result->ended_reason ?? 'no end reason');
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testCraftAndEnchantSecondTickEnchantsPendingSlotAndAdvancesIndex(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Phase Enchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Phase Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $service = resolve(BatchCraftingService::class);
        $afterCraftTick = $service->process($batchCrafting);
        $afterEnchantTick = $service->process($afterCraftTick);

        $this->assertSame('craft', $afterEnchantTick->progress['craft_enchant_phase'] ?? null);
        $this->assertArrayHasKey('pending_enchant_item_id', $afterEnchantTick->progress ?? []);
        $this->assertNull($afterEnchantTick->progress['pending_enchant_item_id']);
        $this->assertNotNull($afterEnchantTick->action_log[1]['enchanted_item'] ?? null);
        $this->assertSame(1, $afterEnchantTick->progress['craft_enchant_index'] ?? null);
    }

    public function testCraftExperienceQueueIncludesTwoRingEntries(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($ringCrafting, 1, false, ['max_level' => 5])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Queue Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $ringEntries = array_filter($queue, fn (array $entry) => ($entry['type'] ?? '') === 'ring');
        $this->assertCount(2, $ringEntries);
    }

    public function testCraftExperienceBuildsFixedSetQueueWithDuplicateSpellsRingsAndShield(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($ringCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($spellCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($armourCrafting, 1, false, ['max_level' => 5])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Experience Queue Dagger', 'type' => ItemType::DAGGER->value, 'crafting_type' => 'weapon', 'default_position' => ItemType::DAGGER->value, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Experience Queue Shield', 'type' => ArmourType::SHIELD->value, 'crafting_type' => 'armour', 'default_position' => ArmourType::SHIELD->value, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Experience Queue Ring', 'type' => ItemType::RING->value, 'crafting_type' => 'ring', 'default_position' => ItemType::RING->value, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Experience Queue Damage Spell', 'type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell', 'default_position' => ItemType::SPELL_DAMAGE->value, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Experience Queue Healing Spell', 'type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell', 'default_position' => ItemType::SPELL_HEALING->value, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $ringEntries = array_filter($queue, fn (array $entry) => ($entry['type'] ?? '') === ItemType::RING->value);
        $damageSpellEntries = array_filter($queue, fn (array $entry) => ($entry['type'] ?? '') === ItemType::SPELL_DAMAGE->value);
        $healingSpellEntries = array_filter($queue, fn (array $entry) => ($entry['type'] ?? '') === ItemType::SPELL_HEALING->value);
        $shieldEntries = array_filter($queue, fn (array $entry) => ($entry['type'] ?? '') === ArmourType::SHIELD->value);
        $this->assertCount(2, $ringEntries);
        $this->assertCount(1, $damageSpellEntries);
        $this->assertCount(1, $healingSpellEntries);
        $this->assertCount(1, $shieldEntries);
    }

    public function testEnchantBatchDoesNotProcessHistoricalRows(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false, ['max_level' => 5])
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Max Level Enchant Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Max Level Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ENCHANT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
        $this->assertNull($result->action_log[0]['enchanted_item'] ?? null);
    }

    public function testEnchantBatchLeavesPartiallyEnchantedItemsUntouched(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $prefix = $this->createItemAffix(['name' => 'Partial Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItemAffix(['name' => 'Clean Target Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $partialItem = $this->createItem(['name' => 'Partially Enchanted Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'item_prefix_id' => $prefix->id, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $cleanItem = $this->createItem(['name' => 'Clean Enchant Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $partialItem->id]);
        $cleanSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $cleanItem->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ENCHANT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
        $this->assertNull($result->action_log[0]['enchanted_item'] ?? null);
        $this->assertNull($cleanSlot->refresh()->item->item_prefix_id);
    }

    public function testMaxLevelAlchemyAmountBatchStillTransmutesWhenEligibleAlchemyItemExists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Max Level Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertSame(1, $result->crafted_count);
        $this->assertSame(1, $result->kept_count);
    }

    public function testAlchemyAmountCraftsSelectedItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $this->createItem(['name' => 'Unselected Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $selectedItem = $this->createItem(['name' => 'Selected Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $selectedItem->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame('Selected Alchemy Item', $result->action_log[0]['alchemy_item']['name']);
    }

    public function testAlchemyAmountStopsWhenSelectedItemIsNotCraftable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $selectedItem = $this->createItem(['name' => 'Unavailable Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 99, 'skill_level_trivial' => 99]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $selectedItem->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
    }

    public function testMaxLevelTrinketryBatchStopsAtMaxLevel(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketry, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'copper_coins' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'Max Level Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::TRINKETRY->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::SKILL_MAXED->value, $result->ended_reason);
    }

    public function testHolyOilsStopWhenAllOilsApplied(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::HOLY_OILS->value, 'progress' => ['all_oils_applied' => true]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::ALL_OILS_APPLIED->value, $result->ended_reason);
    }

    public function testHolyOilsStopWhenNoOilsRemain(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::HOLY_OILS->value, 'progress' => ['no_oils_left' => true]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_OILS_LEFT->value, $result->ended_reason);
    }

    public function testHolyOilsStopWhenSelectedItemsAreExhausted(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::HOLY_OILS->value, 'progress' => ['no_selected_items_left' => true]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT->value, $result->ended_reason);
    }

    public function testCompletedPanelRemainsUntilDismissed(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertTrue($status['completed']);
    }

    public function testActiveLookupExcludesCancelledBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'cancelled_at' => now(), 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::CANCELLED->value]);

        $batchCrafting = resolve(BatchCraftingService::class)->active($character);

        $this->assertNull($batchCrafting);
    }

    public function testActiveLookupExcludesCompletedBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value]);

        $batchCrafting = resolve(BatchCraftingService::class)->active($character);

        $this->assertNull($batchCrafting);
    }

    public function testActiveLookupReturnsNewestActiveBatchById(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'started_at' => now()->addHour()]);
        $newestBatchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'started_at' => now()->subHour()]);

        $batchCrafting = resolve(BatchCraftingService::class)->active($character);

        $this->assertSame($newestBatchCrafting->id, $batchCrafting?->id);
    }

    public function testVisibleLookupReturnsActiveBatchWhenActiveExists(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $activeBatchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->visible($character);

        $this->assertSame($activeBatchCrafting->id, $batchCrafting?->id);
    }

    public function testVisibleLookupReturnsCompletedUndismissedBatchWhenNoActiveExists(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $completedBatchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->visible($character);

        $this->assertSame($completedBatchCrafting->id, $batchCrafting?->id);
    }

    public function testVisibleLookupDoesNotReturnDismissedCompletedBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => now()]);

        $batchCrafting = resolve(BatchCraftingService::class)->visible($character);

        $this->assertNull($batchCrafting);
    }

    public function testVisibleLookupPrefersActiveBatchOverOlderCompletedUndismissedBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now()->subHour(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);
        $activeBatchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->visible($character);

        $this->assertSame($activeBatchCrafting->id, $batchCrafting?->id);
    }

    public function testStatusReturnsTimerProgressFields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'started_at' => now()->subHour(),
            'ends_at' => now()->addHours(7),
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertArrayHasKey('elapsed_seconds', $status['batch']);
        $this->assertArrayHasKey('remaining_seconds', $status['batch']);
        $this->assertArrayHasKey('elapsed_human', $status['batch']);
        $this->assertArrayHasKey('remaining_human', $status['batch']);
        $this->assertArrayHasKey('progress_percent', $status['batch']);
    }

    public function testStatusReturnsActionLogItemSnapshots(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'action_log' => [[
                'ts' => now()->toJSON(),
                'action_type' => 'craft',
                'status' => 'kept',
                'kept_item' => [
                    'name' => 'Clickable Kept Sword',
                    'item_id' => 1,
                    'slot_id' => 10,
                    'item_id_for_modal' => 1,
                    'slot_id_for_modal' => 10,
                    'can_view' => true,
                ],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Clickable Kept Sword', $status['batch']['action_log'][0]['kept_item']['name']);
        $this->assertTrue($status['batch']['action_log'][0]['kept_item']['can_view']);
    }

    public function testStatusExposesCraftEnchantSetPipelineProgressBeforeFinalizationStarts(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_requested' => 23,
                'craft_enchant_set_craft_index' => 23,
                'craft_enchant_set_enchant_index' => 8,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_completed_work_units' => 31,
                'craft_enchant_set_total_work_units' => 69,
                'craft_enchant_set_completed_final_count' => 0,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(23, $status['batch']['craft_enchant_set_craft_completed_count']);
        $this->assertSame(8, $status['batch']['craft_enchant_set_enchant_completed_count']);
        $this->assertSame(0, $status['batch']['craft_enchant_set_finalize_completed_count']);
        $this->assertSame(31, $status['batch']['craft_enchant_set_completed_work_units']);
        $this->assertSame(69, $status['batch']['craft_enchant_set_total_work_units']);
        $this->assertSame(38, $status['batch']['craft_enchant_set_remaining_work_units']);
        $this->assertSame(44, $status['batch']['craft_enchant_set_overall_percent']);
        $this->assertSame(0, $status['batch']['craft_enchant_set_completed_final_count']);
    }

    public function testStatusReflectsActiveRetryStateAfterATickWithOneNormalFailure(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Retry State Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['last_tick_failed_count']);
        $this->assertTrue($result->progress['last_tick_had_failure']);
        $this->assertSame('Crafting attempt failed. No item was produced.', $result->progress['last_tick_failure_reason']);
        $this->assertSame(2, $result->progress['last_tick_retry_delay_seconds']);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['batch']['retry_state']['active']);
        $this->assertSame(1, $status['batch']['retry_state']['failed_count']);
        $this->assertSame(2, $status['batch']['retry_state']['delay_seconds']);
        $this->assertSame('Crafting attempt failed. No item was produced.', $status['batch']['retry_state']['failure_reason']);
    }

    public function testRetryStateClearsAfterANormalTickWithNoFailureDespiteEarlierCumulativeFailures(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Retry Clear Set Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => array_fill(0, 8, ['type' => 'dagger', 'crafting_type' => 'dagger']),
                'craft_set_index' => 0,
                'craft_set_requested' => 8,
                'craft_set_completed' => 0,
            ],
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($batchCrafting->progress['last_tick_had_failure']);
        $this->assertSame(6, $batchCrafting->failed_count);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );

        $result = resolve(BatchCraftingService::class)->process($batchCrafting->refresh());

        $this->assertSame(0, $result->progress['last_tick_failed_count']);
        $this->assertFalse($result->progress['last_tick_had_failure']);
        $this->assertNull($result->progress['last_tick_failure_reason']);
        $this->assertGreaterThan(0, $result->failed_count);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertFalse($status['batch']['retry_state']['active']);
    }

    public function testStatusRetryStateIsInactiveForACompletedBatchWithHistoricalFailures(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'ended_reason' => BatchCraftingEndReason::COMPLETED_DURATION->value,
            'failed_count' => 5,
            'progress' => [
                'last_tick_failed_count' => 5,
                'last_tick_had_failure' => true,
                'last_tick_failure_reason' => 'Crafting attempt failed. No item was produced.',
                'last_tick_retry_delay_seconds' => 2,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertFalse($status['batch']['retry_state']['active']);
        $this->assertSame(0, $status['batch']['retry_state']['failed_count']);
        $this->assertNull($status['batch']['retry_state']['failure_reason']);
    }

    public function testStatusReturnsNonClickableSoldSnapshot(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'action_log' => [[
                'ts' => now()->toJSON(),
                'action_type' => 'craft',
                'status' => 'sold',
                'sold_item' => [
                    'name' => 'Sold Sword',
                    'can_view' => false,
                    'slot_id_for_modal' => null,
                ],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertFalse($status['batch']['action_log'][0]['sold_item']['can_view']);
    }

    public function testStatusReturnsNonClickableDestroyedSnapshot(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'action_log' => [[
                'ts' => now()->toJSON(),
                'action_type' => 'craft',
                'status' => 'destroyed',
                'destroyed_item' => [
                    'name' => 'Destroyed Sword',
                    'can_view' => false,
                    'slot_id_for_modal' => null,
                ],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertFalse($status['batch']['action_log'][0]['destroyed_item']['can_view']);
    }

    public function testStatusReturnsDisenchantedCount(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'action_log' => [[
                'ts' => now()->toJSON(),
                'action_type' => 'craft_and_enchant',
                'status' => 'disenchanted',
                'disenchanted_item' => [
                    'name' => 'Disenchanted Sword',
                    'can_view' => false,
                ],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(1, $status['batch']['counts']['disenchanted']);
    }

    public function testNoGoldStatusIncludesKeptSetSummaryWhenPiecesExist(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::NO_GOLD->value,
            'panel_dismissed_at' => null,
            'action_log' => [[
                'ts' => now()->toJSON(),
                'action_type' => 'craft',
                'status' => 'kept',
                'kept_item' => [
                    'name' => 'Kept Partial Sword',
                    'can_view' => true,
                ],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Kept Partial Sword', $status['batch']['kept_set_summary']['items'][0]['name']);
    }

    public function testDismissedPanelNoLongerReturns(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => now()]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertFalse($status['active']);
        $this->assertFalse($status['completed']);
    }

    public function testInfoAcknowledgementStoresState(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        resolve(BatchCraftingService::class)->acknowledgeInfo($character);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->first());
    }

    public function testAcknowledgedInfoDoesNotAutoShowAgain(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'panel_dismissed_at' => now(), 'info_acknowledged' => true]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertFalse($status['show_info']);
    }

    public function testCompleteForDeathEndsActiveBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        resolve(BatchCraftingService::class)->completeForDeath($character);

        $this->assertSame(BatchCraftingEndReason::DIED->value, \App\Flare\Models\BatchCrafting::where('character_id', $character->id)->first()->ended_reason);
    }

    public function testCraftAndEnchantWithDisenchantRemovesItemThroughExistingServicePath(): void
    {
        Bus::fake();
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Batch Disenchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Batch Disenchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $service = resolve(BatchCraftingService::class);
        $result = $service->process($batchCrafting);
        $result = $service->process($result);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame('disenchanted', $result->action_log[1]['status']);
        $this->assertFalse($result->action_log[1]['disenchanted_item']['can_view']);
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function testStatusIncludesInventoryPercent(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertArrayHasKey('inventory_percent', $status['batch']);
        $this->assertSame(0, $status['batch']['inventory_percent']);
    }

    public function testStatusIncludesSkillXpData(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 3, 'xp' => 50, 'xp_max' => 200]);
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $enchantSkillData = collect($status['batch']['skills'] ?? [])->first(fn ($s) => $s['key'] === 'enchanting');
        $this->assertNotNull($enchantSkillData);
        $this->assertSame(3, $enchantSkillData['level']);
        $this->assertSame(50, $enchantSkillData['current_xp']);
        $this->assertSame(200, $enchantSkillData['next_level_xp']);
        $this->assertFalse($enchantSkillData['is_maxed']);
    }

    public function testStatusSkillIsMaxedWhenAtMaxLevel(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $enchantingSkill = $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        });
        $enchantingSkill->update(['level' => $enchantingSkill->max_level, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $enchantSkillData = collect($status['batch']['skills'] ?? [])->first(fn ($s) => $s['key'] === 'enchanting');
        $this->assertNotNull($enchantSkillData);
        $this->assertTrue($enchantSkillData['is_maxed']);
        $this->assertSame(100, $enchantSkillData['xp_percent']);
    }

    public function testStatusMissingSkillDoesNotCrash(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertArrayHasKey('skills', $status['batch']);
        $this->assertIsArray($status['batch']['skills']);
    }

    public function testEnchantFailureDoesNotIncrementCraftedCount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Enchant Fail Target', 'type' => 'weapon', 'cost' => 1]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
    }

    public function testCraftExperienceModeStopsWhenAllCraftingSkillsAreMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::SKILL_MAXED->value, $result->ended_reason);
    }

    public function testCraftSpecificItemModeStopsWhenAmountReached(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'craft_amount' => 3,
                'craft_specific_count' => 3,
                'specific_crafting_type' => 'weapon',
                'specific_item_type' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testCraftAmountCompletionSummaryReportsAllCrafted(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 3],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('all', $status['batch']['completion_summary'] ?? null);
    }

    public function testCraftAmountCompletionSummaryReportsSomeCrafted(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 1],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('some', $status['batch']['completion_summary'] ?? null);
    }

    public function testCraftAmountCompletionSummaryReportsNoneCrafted(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 0],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('none', $status['batch']['completion_summary'] ?? null);
    }

    public function testAlchemyExperienceModeStopsWhenAlchemySkillIsMaxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $alchemySkill = $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        });
        $alchemySkill->update(['level' => $alchemySkill->max_level, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::SKILL_MAXED->value, $result->ended_reason);
    }

    public function testAlchemyAmountModeStopsWhenAmountReached(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 1000, 'shards' => 1000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'craft_amount' => 2, 'alchemy_amount_count' => 2],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testTrinketryDoesNotRequireAmount(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 1000, 'copper_coins' => 1000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testCraftExperienceStopsWhenNoEligibleItemsExist(): void
    {
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($ringCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 2]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
    }

    public function testHolyOilsStopsOnInsufficientGoldDustForActualCost(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 100, 'inventory_max' => 10]);
        $targetItem = $this->createItem(['name' => 'Holy Target Sword', 'type' => 'weapon', 'holy_stacks' => 1, 'cost' => 1]);
        $targetSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $targetItem->id]);
        $oilItem = $this->createItem(['name' => 'Level 2 Holy Oil', 'type' => 'alchemy', 'holy_level' => 2, 'can_use_on_other_items' => true]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oilItem->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$targetSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST->value, $result->ended_reason);
    }

    public function testBatchStartWritesStartedLogEntry(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('batchStarted')->once();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertSame(1, BatchCrafting::where('character_id', $character->id)->count());
    }

    public function testCharacterPayloadIncludesActiveBatchCraftingState(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(10),
        ]);

        $payload = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character->refresh());

        $this->assertTrue($payload['is_batch_crafting_running']);
        $this->assertGreaterThan(0, $payload['batch_crafting_time_out']);
    }

    public function testCharacterPayloadIncludesInactiveBatchCraftingState(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $payload = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character->refresh());

        $this->assertFalse($payload['is_batch_crafting_running']);
        $this->assertSame(0, $payload['batch_crafting_time_out']);
    }

    public function testUpdateCharacterStatusIncludesActiveBatchCraftingState(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(10),
        ]);

        $event = new UpdateCharacterStatus($character->refresh());

        $this->assertTrue($event->characterStatuses['is_batch_crafting_running']);
        $this->assertGreaterThan(0, $event->characterStatuses['batch_crafting_time_out']);
    }

    public function testStatusReturnsActiveVisiblePanelDataAfterStartingCraftForExperience(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $service = resolve(BatchCraftingService::class);

        $service->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $status = $service->status($character->refresh());

        $this->assertTrue($status['active']);
        $this->assertTrue($status['is_visible']);
        $this->assertTrue($status['can_cancel']);
        $this->assertSame(BatchCraftingType::CRAFT->value, $status['batch']['batch_type']);
    }

    public function testStatusReturnsActiveVisiblePanelDataAfterStartingCraftAmount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Panel Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $service = resolve(BatchCraftingService::class);

        $service->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);
        $status = $service->status($character->refresh());

        $this->assertTrue($status['active']);
        $this->assertTrue($status['is_visible']);
        $this->assertSame('specific_item', $status['batch']['mode']);
    }

    public function testStatusReturnsActiveVisiblePanelDataAfterStartingCraftAndEnchant(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Panel Craft And Enchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Panel Craft And Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $service = resolve(BatchCraftingService::class);

        $service->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);
        $status = $service->status($character->refresh());

        $this->assertTrue($status['active']);
        $this->assertTrue($status['is_visible']);
        $this->assertSame(BatchCraftingType::CRAFT_AND_ENCHANT->value, $status['batch']['batch_type']);
    }

    public function testStatusReturnsCompletedVisiblePanelDataAfterCancel(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $service = resolve(BatchCraftingService::class);

        $service->cancel($character);
        $status = $service->status($character);

        $this->assertTrue($status['completed']);
        $this->assertTrue($status['is_visible']);
    }

    public function testStatusReturnsNotVisibleAfterDismiss(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $service = resolve(BatchCraftingService::class);

        $service->cancel($character);
        $service->dismiss($character);
        $status = $service->status($character);

        $this->assertFalse($status['is_visible']);
    }

    public function testStatusPayloadContainsFieldsNeededByFrontendPanel(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::CRAFT->value]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertArrayHasKey('active', $status);
        $this->assertArrayHasKey('completed', $status);
        $this->assertArrayHasKey('is_visible', $status);
        $this->assertArrayHasKey('can_cancel', $status);
        $this->assertArrayHasKey('can_dismiss', $status);
        $this->assertArrayHasKey('batch_type', $status['batch']);
    }

    public function testSuccessfulActionWritesSucceededLogEntry(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('actionAttempted')->once();
        $logger->shouldReceive('actionSucceeded')->once();
        $logger->shouldReceive('failedRoll')->never();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(1, $batchCrafting->refresh()->crafted_count);
    }

    public function testFailedRollWritesFailedRollLogEntry(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'failure' => 'Craft failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('actionAttempted')->once();
        $logger->shouldReceive('failedRoll')->once();
        $logger->shouldReceive('actionSucceeded')->never();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(1, $batchCrafting->refresh()->failed_count);
    }

    public function testHardStopWritesHardStopLogEntry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 0]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::CRAFT->value]);
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('hardStop')->once();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD->value, $batchCrafting->refresh()->ended_reason);
    }

    public function testCancelWritesCancelledLogEntry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('batchCancelled')->once();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->cancel($character);

        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, BatchCrafting::where('character_id', $character->id)->first()->ended_reason);
    }

    public function testCompleteWritesCompletedLogEntry(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn(['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED]);
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('batchCompleted')->once();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->refresh()->ended_reason);
    }

    public function testExceptionWritesExceptionLogEntry(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andThrow(new RuntimeException('Processor failed.'));
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('exceptionCaught')->once();
        $logger->shouldReceive('hardStop')->once();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $batchCrafting->refresh()->ended_reason);
    }

    public function testStatusEventDispatchesAfterSuccessfulAction(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
    }

    public function testAdminMonitoringEventDispatchesAfterSuccessfulAction(): void
    {
        Event::fake([BatchCraftingMonitoringUpdated::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        Event::assertDispatched(BatchCraftingMonitoringUpdated::class);
    }

    public function testStatusEventDispatchesAfterFailedRoll(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'failure' => 'Craft failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
    }

    public function testStatusEventDispatchesAfterHardStop(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 0]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'batch_type' => BatchCraftingType::CRAFT->value]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->process($batchCrafting);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
    }

    public function testStatusEventDispatchesAfterCancel(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->cancel($character);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
    }

    public function testStatusEventDispatchesAfterDismiss(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::DIED->value,
            'panel_dismissed_at' => null,
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->dismiss($character);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
    }

    public function testAutomationLogUpdateDispatchesAfterStart(): void
    {
        Event::fake([AutomationLogUpdate::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testAutomationLogUpdateDispatchesAfterSuccessfulTickWithUsefulMessage(): void
    {
        Event::fake([AutomationLogUpdate::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event) {
            return $event->message === 'Batch crafting update: crafted 1 item.';
        });
    }

    public function testAutomationLogUpdateDispatchesAfterCancel(): void
    {
        Event::fake([AutomationLogUpdate::class]);
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->cancel($character);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event) {
            return $event->message === 'Batch crafting was cancelled.';
        });
    }

    public function testAutomationLogUpdateDispatchesAfterCompletion(): void
    {
        Event::fake([AutomationLogUpdate::class]);
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'ends_at' => now()->subMinute(),
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->process($batchCrafting);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event) {
            return $event->message === 'Batch crafting completed. Reason: Completed Duration';
        });
    }

    public function testProcessCompletesWithBatchCraftingSetFullReason(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL,
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testStatusIncludesBatchCraftingSetSummary(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $status = (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
            resolve(EnchantingService::class),
            resolve(BatchCraftingSetService::class),
            resolve(HolyItemService::class),
        ))->status($character);

        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS, $status['batch']['batch_crafting_set']['max_slots']);
        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS, $status['batch']['batch_crafting_set']['remaining_slots']);
    }

    public function testCraftExperienceBuildsTwentyThreeInternalTargets(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($ringCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($spellCrafting, 1, false, ['max_level' => 5])
            ->assignSkill($armourCrafting, 1, false, ['max_level' => 5])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $itemsByType = [];

        foreach (array_merge(ItemType::validWeapons(), ArmourType::allTypes(), [ItemType::RING->value, ItemType::SPELL_DAMAGE->value, ItemType::SPELL_HEALING->value]) as $queueTargetType) {
            $itemsByType[$queueTargetType] = $this->createItem(['name' => 'Queue Target ' . $queueTargetType, 'type' => $queueTargetType, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        }

        $craftingService = Mockery::mock(CraftingService::class);
        $craftingService->shouldReceive('fetchCraftableItems')->andReturnUsing(function ($character, array $params, bool $includeDetails = false) use ($itemsByType) {
            if ($params['crafting_type'] === 'armour') {
                return new EloquentCollection(array_map(fn (string $type) => $itemsByType[$type], ArmourType::allTypes()));
            }

            if ($params['crafting_type'] === 'ring') {
                return new EloquentCollection([$itemsByType[ItemType::RING->value]]);
            }

            if ($params['crafting_type'] === 'spell') {
                return new EloquentCollection([
                    $itemsByType[ItemType::SPELL_DAMAGE->value],
                    $itemsByType[ItemType::SPELL_HEALING->value],
                ]);
            }

            return new EloquentCollection([$itemsByType[$params['crafting_type']]]);
        });
        $craftingService->shouldReceive('craftForBatch')->andReturn(['success' => false, 'item' => null, 'reason' => 'failed_roll']);
        $processor = new BatchCraftingProcessor(
            $craftingService,
            resolve(AlchemyService::class),
            resolve(TrinketCraftingService::class),
            resolve(EnchantingService::class),
            resolve(HolyItemService::class),
            resolve(MultiInventoryActionService::class),
            resolve(UseItemService::class),
            resolve(BatchCraftingSetService::class),
            resolve(InventorySetService::class),
            resolve(HandleUpdatingCraftingGlobalEventGoal::class),
            resolve(HandleUpdatingEnchantingGlobalEventGoal::class),
            resolve(\App\Game\Messages\Handlers\ServerMessageHandler::class),
        );

        $result = $processor->processOneTick($batchCrafting, $character->refresh());

        $this->assertCount(23, $batchCrafting->refresh()->progress['craft_experience_queue'], $result['end_reason']?->value ?? 'no end reason');
        $this->assertSame(6, array_sum($result['counts']));
    }

    public function testBatchCraftingSetMoveFailureStopsBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL,
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertNotNull($result->ended_reason);
    }

    public function testBatchCraftingSetFullUsesCorrectEndReason(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);
        $mockSetService = Mockery::mock(BatchCraftingSetService::class);
        $mockSetService->shouldReceive('canAccept')->andReturn(false);
        $this->app->instance(BatchCraftingSetService::class, $mockSetService);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testCraftExperienceKeptOutputMovesToBatchCraftingSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false, ['xp' => 0, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Keep Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $batchCraftingSet = $character->refresh()->inventorySets()
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();

        $this->assertNotNull($batchCraftingSet);
        $this->assertGreaterThan(0, $batchCraftingSet->slots()->count());
    }

    public function testCraftAndEnchantExperienceKeptOutputMovesToBatchCraftingSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false, ['xp' => 0, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'CE Keep Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Keep Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $enchantedItem = $item->replicate();
                    $enchantedItem->name = $item->name.' Enchanted '.(((int) \App\Flare\Models\Item::max('id')) + 1);
                    $enchantedItem->item_prefix_id = $prefix->id;
                    $enchantedItem->save();

                    return ['success' => true, 'item' => $enchantedItem, 'reason' => null];
                });
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_affix_ids' => [$prefix->id]],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $batchCraftingSet = $character->refresh()->inventorySets()
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();

        $this->assertNotNull($batchCraftingSet);
        $this->assertGreaterThan(0, $batchCraftingSet->slots()->count());
    }

    public function testCraftAndEnchantExperienceRecordsActionWhenEnchantFails(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Enchant Fail Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->refresh()->failed_count);
        $this->assertNull($result->ended_reason);
        $this->assertNull($result->completed_at);
    }

    public function testCraftAndEnchantForExperienceDoesNotRequireCraftExperienceSkill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
        $this->assertArrayNotHasKey('craft_experience_skill', $batchCrafting->progress ?? []);
    }

    public function testCraftAndEnchantForExperienceIgnoresSubmittedCraftExperienceSkill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'craft_experience_skill' => 'enchanting'],
        ]);

        $this->assertArrayNotHasKey('craft_experience_skill', $batchCrafting->progress ?? []);
    }

    public function testBatchDispositionSellDirectlyCreditsGoldWithoutCreatingInventorySlots(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 20]);
        $item = $this->createItem(['name' => 'Bulk Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 500, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 2],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $soldAction = collect($result->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'sell');
        $this->assertNotNull($soldAction);
        $this->assertGreaterThan(0, $soldAction['gold_gained'] ?? 0);
    }

    public function testBatchCraftingSetMoveFailureForNonFullReasonContinuesBatchInsteadOfEndingIt(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Not Owned Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $mockBatchSet = Mockery::mock(BatchCraftingSetService::class);
        $mockBatchSet->shouldReceive('canAccept')->andReturn(true);
        $mockBatchSet->shouldReceive('createItemInBatchCraftingSet')
            ->andReturn(['success' => false, 'reason' => 'not_owned', 'set_slot' => null]);
        $this->app->instance(BatchCraftingSetService::class, $mockBatchSet);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->failed_count);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertNotNull($result->completed_at);
    }

    public function testStatusPayloadTopLevelKeysAreUnique(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(array_keys($status), array_unique(array_keys($status)));
        $this->assertArrayNotHasKey('active', $status['batch'] ?? []);
    }

    public function testCraftExperienceOptionsAbsentWhenAllCraftingSkillsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $craftOptions = collect($status['craft_experience_options'])->where('batch_type', BatchCraftingType::CRAFT->value);

        $this->assertTrue($craftOptions->isEmpty());
    }

    public function testCraftExperienceOptionsIncludeOnlyNonMaxedSkills(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 2, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $craftOptionValues = collect($status['craft_experience_options'])
            ->where('batch_type', BatchCraftingType::CRAFT->value)
            ->pluck('value')
            ->values()
            ->all();

        $this->assertSame(['armour'], $craftOptionValues);
    }

    public function testCraftForExperienceRejectedWhenAllFourCraftingSkillsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
    }

    public function testCraftForExperienceAvailableWhenAtLeastOneCraftingSkillIsNotMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
    }

    public function testCraftForExperienceDoesNotRequireCraftExperienceSkill(): void
    {
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertArrayNotHasKey('craft_experience_skill', $batchCrafting->progress ?? []);
    }

    public function testCraftForExperienceIgnoresSubmittedCraftExperienceSkill(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'craft_experience_skill' => 'weapon'],
        ]);

        $this->assertArrayNotHasKey('craft_experience_skill', $batchCrafting->progress ?? []);
    }

    public function testCraftForExperienceStatusExposesNonMaxedRelevantSkillProgressData(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 2, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $skillsBeingTrained = collect($status['batch']['skills_being_trained']);
        $armourEntry = $skillsBeingTrained->firstWhere('key', 'armour');
        $weaponEntry = $skillsBeingTrained->firstWhere('key', 'weapon');

        $this->assertNotNull($armourEntry);
        $this->assertFalse($armourEntry['is_maxed']);
        $this->assertNotNull($weaponEntry);
        $this->assertTrue($weaponEntry['is_maxed']);
    }

    public function testCraftForExperienceQueueExcludesWeaponEntriesWhenWeaponCraftingIsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 1, false)
            ->assignSkill($ringCrafting, 1, false)
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $weaponEntries = array_filter($queue, fn (array $entry) => in_array($entry['crafting_type'] ?? '', ItemType::validWeapons(), true));

        $this->assertCount(0, $weaponEntries);
    }

    public function testCraftForExperienceQueueExcludesArmourAndShieldEntriesWhenArmourCraftingIsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false)
            ->assignSkill($armourCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 1, false)
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $armourEntries = array_filter($queue, fn (array $entry) => ($entry['crafting_type'] ?? '') === 'armour');

        $this->assertCount(0, $armourEntries);
    }

    public function testCraftForExperienceQueueExcludesRingEntriesWhenRingCraftingIsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false)
            ->assignSkill($armourCrafting, 1, false)
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $ringEntries = array_filter($queue, fn (array $entry) => ($entry['crafting_type'] ?? '') === 'ring');

        $this->assertCount(0, $ringEntries);
    }

    public function testCraftForExperienceQueueExcludesSpellDamageAndSpellHealingEntriesWhenSpellCraftingIsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false)
            ->assignSkill($armourCrafting, 1, false)
            ->assignSkill($ringCrafting, 1, false)
            ->assignSkill($spellCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $spellEntries = array_filter($queue, fn (array $entry) => ($entry['crafting_type'] ?? '') === 'spell');

        $this->assertCount(0, $spellEntries);
    }

    public function testCraftForExperienceQueueIncludesOnlyNonMaxedSkillCategories(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($armourCrafting, 1, false)
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($spellCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_experience_queue'] ?? [];
        $craftingTypesUsed = array_unique(array_map(fn (array $entry) => $entry['crafting_type'] ?? '', $queue));
        sort($craftingTypesUsed);

        $this->assertSame(['armour', 'spell'], $craftingTypesUsed);
    }

    public function testSpecificItemCraftStartsWithOneMinutePendingTimer(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Pending Timer Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftAmountContinuesAfterAnIndividualFailedAttemptWhenContinuingIsPossible(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 0],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1, 'crafted_count' => 1],
            'actions' => [
                ['action' => 'craft', 'status' => 'failed', 'failure' => 'Crafting service did not produce an inventory slot.'],
                ['action' => 'craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]],
            ],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertSame(1, $result->failed_count);
        $this->assertSame(1, $result->crafted_count);
    }

    public function testCraftAndEnchantSpecificItemStartsWithOneMinutePendingTimer(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Pending Timer Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Pending Timer Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testAlchemyAmountStartsWithOneMinutePendingTimer(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Pending Timer Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testHolyOilsStartsWithOneMinutePendingTimer(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftSetDoesNotRequireASelectedSetToStart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);

        $this->assertArrayNotHasKey('selected_set_id', $batchCrafting->progress ?? []);
    }

    public function testCraftSetStartsWithOneMinutePendingTimer(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftSetContinuesToNextSetItemAfterAnIndividualFailedAttemptWhenContinuingIsPossible(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger'], ['type' => 'sword', 'crafting_type' => 'sword']],
                'craft_set_index' => 0,
                'craft_set_requested' => 2,
                'craft_set_completed' => 0,
            ],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['skipped_count' => 1, 'crafted_count' => 1, 'kept_count' => 1],
            'actions' => [
                ['action' => 'craft_set', 'status' => 'skipped', 'failure' => 'No craftable item found for type: dagger.'],
                ['action' => 'craft_set', 'status' => 'crafted', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]],
            ],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertSame(1, $result->skipped_count);
        $this->assertSame(1, $result->crafted_count);
    }

    public function testCraftSetPutsCraftedItemsIntoCraftedItemsSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($craftedItemsSet);
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $this->assertSame(1, $result->progress['craft_set_completed'] ?? null, $result->ended_reason ?? 'no end reason');
        $keptAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_set');
        $this->assertNotNull($keptAction['kept_item']['set_slot_id'] ?? null);
    }

    public function testCraftSetSellDispositionDoesNotCreateCraftedItemsSetOutput(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->sold_count);
        $this->assertGreaterThan(0, collect($result->action_log)->sum('gold_gained'));
    }

    public function testCraftSetDestroyDispositionDoesNotCreateCraftedItemsSetOutput(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Destroy Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->destroyed_count);
    }

    public function testCraftSetStopsWhenCraftedItemsSetDoesNotHaveEnoughSpace(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);
        $craftedItemsSet->update(['max_slots' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $this->createItem()->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testEnchantSetValidatesSelectedSetOwnership(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Set Ownership Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'set', 'selected_set_id' => $otherSet->id, 'enchant_affix_ids' => [$prefix->id]],
        ]);
    }

    public function testEnchantSetProcessesEligibleSetItems(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Enchant Set Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => 'set',
                'selected_set_id' => $set->id,
                'enchant_affix_ids' => [$prefix->id],
                'enchant_set_total' => 1,
                'enchant_set_completed' => 0,
                'enchant_set_skipped' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['enchant_set_completed'] ?? null, $result->ended_reason ?? 'no end reason');
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function testHolyOilsSetValidatesSelectedSetOwnership(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $otherSet->id],
        ]);
    }

    public function testHolyOilsSetCalculatesTotalRemainingStacksAndOilsNeeded(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $itemOne = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $itemTwo = $this->createItem(['type' => 'weapon', 'holy_stacks' => 3]);
        HolyStack::create(['item_id' => $itemTwo->id, 'devouring_darkness_bonus' => 0, 'stat_increase_bonus' => 0]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $itemOne->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $itemTwo->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 5]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id],
        ]);

        $this->assertSame(4, $batchCrafting->progress['holy_oil_total_stacks'] ?? null);
        $this->assertSame(4, $batchCrafting->progress['holy_oil_requested_applications'] ?? null);
    }

    public function testHolyOilsSetAppliesOilsOnlyToItemsWithRemainingStacks(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $maxedItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        HolyStack::create(['item_id' => $maxedItem->id, 'devouring_darkness_bonus' => 0, 'stat_increase_bonus' => 0]);
        $eligibleItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $maxedSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $maxedItem->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $eligibleItem->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id, 'holy_oil_total_stacks' => 1, 'holy_oil_requested_applications' => 1, 'holy_oil_completed_applications' => 0],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $maxedSlot->refresh()->item->holy_stacks_applied);
        $this->assertSame(1, $result->applied_count);
    }

    public function testHolyOilsSetStopsWhenNoSelectedOilsRemain(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id, 'holy_oil_total_stacks' => 1, 'holy_oil_requested_applications' => 1, 'holy_oil_completed_applications' => 0],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_OILS_LEFT->value, $result->ended_reason);
    }

    public function testDisenchantDispositionRecordsGoldDustGained(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Disenchant Gold Dust Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Disenchant Gold Dust Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->action_log[1]['gold_dust_gained'] ?? 0, $result->ended_reason ?? 'no end reason');

        $this->assertGreaterThan(0, $result->action_log[1]['gold_dust_gained'] ?? 0);
    }

    public function testListDispositionCreatesMarketBoardRowUsingSuppliedListingPrice(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'List Price Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'List Price Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id], 'listing_price' => 777],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotNull(MarketBoard::where('character_id', $character->id)->where('listed_price', 777)->first());
    }

    public function testKeptCraftedItemActionLogIncludesSetSlotId(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Set Slot Id Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $keptAction = collect($result->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'keep');
        $this->assertNotNull($keptAction['kept_item']['set_slot_id'] ?? null);
    }

    public function testStatusExposesChartPointsForEntireRun(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Chart Point Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $currencyPoints = $status['batch']['chart_points']['currency'] ?? [];
        $outcomePoints = $status['batch']['chart_points']['outcomes'] ?? [];

        $this->assertNotEmpty($currencyPoints);
        $this->assertGreaterThan(0, $outcomePoints[0]['success'] ?? 0);
    }

    public function testCancelUpdatesStatusImmediatelyAndBroadcasts(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        resolve(BatchCraftingService::class)->cancel($character);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('cancelled_at')->first());
    }

    public function testCancelExposesDismissStateInsteadOfCancelState(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        resolve(BatchCraftingService::class)->cancel($character);
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertFalse($status['can_cancel'] ?? null);
        $this->assertTrue($status['can_dismiss'] ?? null);
    }

    public function testCancelledBatchDoesNotContinueWhenProcessedAgain(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        resolve(BatchCraftingService::class)->cancel($character);
        $cancelledAt = $batchCrafting->refresh()->cancelled_at;

        $result = resolve(BatchCraftingService::class)->process($batchCrafting->refresh());

        $this->assertSame($cancelledAt->toJSON(), $result->cancelled_at->toJSON());
        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
    }

    public function testNewStandaloneEnchantSetStartIsRejected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $item = $this->createItem(['name' => 'Enchant Set No Event Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Set No Event Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'set', 'selected_set_id' => $set->id, 'enchant_affix_ids' => [$prefix->id]],
        ]);
    }

    public function testEventEnchantIsStillUnavailableWithoutEventEligibility(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);
    }

    public function testCraftSetSelectsHighestCraftableItemNotLowest(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $highItem = $this->createItem(['name' => 'Craft Set High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $slot = $craftedItemsSet?->slots()->first();
        $this->assertSame($highItem->id, $slot->item_id ?? null);
    }

    public function testCraftSetStatusExposesSelectedSetRequestedCompletedAndGoldTotals(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Status Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $character = $character->refresh();
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame($set->id, $status['batch']['selected_set']['id'] ?? null);
        $this->assertSame(1, $status['batch']['requested_amount'] ?? null);
        $this->assertSame(1, $status['batch']['completed_amount'] ?? null);
        $this->assertSame($character->gold, $status['batch']['gold_left'] ?? null);
    }

    public function testCraftSetStatusExposesClickableItemSnapshotData(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Snapshot Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Craft Set Snapshot Dagger', $status['batch']['craft_set_current_item']['name'] ?? null);
    }

    public function testEnchantSetStatusExposesSelectedSetEligibleTotalAndAffixes(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Enchant Set Status Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Set Status Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => 'set',
                'selected_set_id' => $set->id,
                'enchant_affix_ids' => [$prefix->id],
                'enchant_set_total' => 1,
                'enchant_set_completed' => 0,
                'enchant_set_skipped' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $character = $character->refresh();
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame($set->id, $status['batch']['selected_set']['id'] ?? null);
        $this->assertSame(1, $status['batch']['enchant_set_total'] ?? null);
        $this->assertSame(['Enchant Set Status Prefix'], $status['batch']['enchant_affix_names'] ?? null);
        $this->assertSame($character->gold, $status['batch']['gold_left'] ?? null);
    }

    public function testEnchantSetExposesEnchantedCountClearly(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Enchant Set Count Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Set Count Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => 'set',
                'selected_set_id' => $set->id,
                'enchant_affix_ids' => [$prefix->id],
                'enchant_set_total' => 1,
                'enchant_set_completed' => 0,
                'enchant_set_skipped' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(1, $status['batch']['counts']['enchanted'] ?? null);
    }

    public function testHolyOilsSetStatusExposesStackOilAndBonusTotals(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id, 'holy_oil_total_stacks' => 1, 'holy_oil_requested_applications' => 1, 'holy_oil_completed_applications' => 0],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $character = $character->refresh();
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(1, $status['batch']['holy_oil_total_stacks'] ?? null);
        $this->assertSame(1, $status['batch']['holy_oil_completed_applications'] ?? null);
        $this->assertSame(0, $status['batch']['holy_oil_remaining_applications'] ?? null);
        $this->assertGreaterThan(0, $status['batch']['holy_oil_total_stat_bonus_applied'] ?? 0);
        $this->assertGreaterThanOrEqual(0, $status['batch']['holy_oil_total_devouring_darkness_bonus_applied'] ?? -1);
        $this->assertGreaterThan(0, $status['batch']['holy_oil_gold_dust_spent'] ?? 0);
        $this->assertSame($character->gold_dust, $status['batch']['gold_dust_left'] ?? null);
    }

    public function testHolyOilsSetSkipsItemsWithNoRemainingStacksAndCountsSkips(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $maxedItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        HolyStack::create(['item_id' => $maxedItem->id, 'devouring_darkness_bonus' => 0, 'stat_increase_bonus' => 0]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $maxedItem->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id, 'holy_oil_total_stacks' => 0, 'holy_oil_requested_applications' => 0, 'holy_oil_completed_applications' => 0],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::ALL_OILS_APPLIED->value, $result->ended_reason);
        $this->assertSame(0, $result->applied_count);
    }

    public function testDisenchantingSkillProgressAppearsWhenNotMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::DISENCHANTING->value;
        })->update(['level' => 2, 'max_level' => 400, 'xp' => 10, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Disenchant Progress Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Disenchant Progress Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $disenchantingSkill = collect($status['batch']['skills'])->firstWhere('key', 'disenchanting');
        $this->assertNotNull($disenchantingSkill);
        $this->assertFalse($disenchantingSkill['is_maxed']);
    }

    public function testDisenchantingSkillProgressDoesNotAppearWhenMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::DISENCHANTING->value;
        })->update(['level' => 5, 'max_level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Disenchant Maxed Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Disenchant Maxed Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $disenchantingSkill = collect($status['batch']['skills'])->firstWhere('key', 'disenchanting');
        $this->assertNull($disenchantingSkill);
    }

    public function testAlchemyAmountRemainsValidWhenAlchemyIsMaxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 5, 'max_level' => 5]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Alchemy Amount Maxed Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $this->assertNotNull($batchCrafting->id);
        $this->assertSame('amount', $batchCrafting->progress['alchemy_mode'] ?? null);
    }

    public function testCraftSetQueueIncludesEveryValidWeaponType(): void
    {
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();

        $weaponTypes = collect($queue)
            ->whereIn('type', ItemType::validWeapons())
            ->pluck('type')
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing(ItemType::validWeapons(), $weaponTypes);
    }

    public function testCraftSetQueueIncludesEveryArmourTypeIncludingShield(): void
    {
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();

        $armourTypes = collect($queue)
            ->where('crafting_type', 'armour')
            ->pluck('type')
            ->values()
            ->all();

        $this->assertContains('shield', $armourTypes);
        $this->assertEqualsCanonicalizing(ArmourType::allTypes(), $armourTypes);
    }

    public function testCraftSetQueueIncludesExactlyTwoRingEntries(): void
    {
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();

        $ringCount = collect($queue)
            ->where('type', ItemType::RING->value)
            ->where('crafting_type', 'ring')
            ->count();

        $this->assertSame(2, $ringCount);
    }

    public function testCraftSetQueueIncludesSpellDamageAndSpellHealing(): void
    {
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();

        $spellTypes = collect($queue)
            ->where('crafting_type', 'spell')
            ->pluck('type')
            ->values()
            ->all();

        $this->assertContains(ItemType::SPELL_DAMAGE->value, $spellTypes);
        $this->assertContains(ItemType::SPELL_HEALING->value, $spellTypes);
    }

    public function testCraftSetRequestedCountEqualsRealQueueCount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $set->id],
        ]);

        $this->assertSame(count(resolve(BatchCraftingProcessor::class)->craftSetQueue()), $batchCrafting->progress['craft_set_requested'] ?? null);
    }

    public function testCraftSetStatusExposesRequestedCompletedAndRemainingFromRealQueue(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => $queue,
                'craft_set_index' => 0,
                'craft_set_requested' => count($queue),
                'craft_set_completed' => 2,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(count($queue), $status['batch']['requested_amount'] ?? null);
        $this->assertSame(2, $status['batch']['completed_amount'] ?? null);
        $this->assertSame(count($queue) - 2, $status['batch']['remaining_amount'] ?? null);
    }

    public function testCraftSetCompletionSummaryReportsFullSetCrafted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => $queue,
                'craft_set_index' => count($queue),
                'craft_set_requested' => count($queue),
                'craft_set_completed' => count($queue),
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('all', $status['batch']['completion_summary'] ?? null);
    }

    public function testCraftSetCompletionSummaryReportsPartialSetCrafted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => $queue,
                'craft_set_index' => 2,
                'craft_set_requested' => count($queue),
                'craft_set_completed' => 2,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('some', $status['batch']['completion_summary'] ?? null);
    }

    public function testCraftSetCompletionSummaryReportsNoSetItemsCrafted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => $queue,
                'craft_set_index' => 0,
                'craft_set_requested' => count($queue),
                'craft_set_completed' => 0,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('none', $status['batch']['completion_summary'] ?? null);
    }

    public function testCraftSetSelectsHighestCraftableArmourItem(): void
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($armourCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Low Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $highItem = $this->createItem(['name' => 'Craft Set High Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'craft_set_queue' => [['type' => 'helmet', 'crafting_type' => 'armour']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame($highItem->id, $craftedItemsSet?->slots()->first()->item_id ?? null);
    }

    public function testCraftAndEnchantSpecificStatusExposesCurrentCraftedAndEnchantedItems(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item'],
            'action_log' => [[
                'ts' => now()->toJSON(),
                'crafted_item' => ['name' => 'Crafted Specific Panel Item'],
                'enchanted_item' => ['name' => 'Enchanted Specific Panel Item'],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Crafted Specific Panel Item', $status['batch']['current_crafted_item_snapshot']['name'] ?? null);
        $this->assertSame('Enchanted Specific Panel Item', $status['batch']['current_enchanted_item_snapshot']['name'] ?? null);
    }

    public function testHolyOilsSelectedGearStatusExposesApplicationsAndCurrentItems(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Selected Holy Target', 'type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['name' => 'Selected Holy Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'selected'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(1, $status['batch']['holy_oil_requested_applications'] ?? null);
        $this->assertSame(1, $status['batch']['holy_oil_completed_applications'] ?? null);
        $this->assertSame(0, $status['batch']['holy_oil_remaining_applications'] ?? null);
        $this->assertSame('Selected Holy Target', $status['batch']['holy_oil_current_target_item']['name'] ?? null);
        $this->assertSame('Selected Holy Oil', $status['batch']['holy_oil_current_oil_item']['name'] ?? null);
    }

    public function testAlchemyAmountStatusExposesRequestedCompletedAndRemainingAmount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Alchemy Amount Status Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 3, 'alchemy_amount_count' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(3, $status['batch']['requested_amount'] ?? null);
        $this->assertSame(1, $status['batch']['completed_amount'] ?? null);
        $this->assertSame(2, $status['batch']['remaining_amount'] ?? null);
    }

    public function testCraftAndEnchantStatusExposesCraftedAndEnchantedSnapshotListsFromSameAction(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item'],
            'action_log' => [[
                'ts' => now()->toJSON(),
                'crafted_item' => ['name' => 'Dual Action Crafted Item', 'can_view' => false],
                'enchanted_item' => ['name' => 'Dual Action Enchanted Item', 'can_view' => false],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Dual Action Crafted Item', $status['batch']['crafted_item_snapshots'][0]['display_name'] ?? null);
        $this->assertSame('Dual Action Enchanted Item', $status['batch']['enchanted_item_snapshots'][0]['display_name'] ?? null);
    }

    public function testEnchantSetStatusUsesProgressEnchantedTotalInsteadOfCappedActionLog(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'progress' => [
                'enchant_mode' => 'set',
                'selected_set_id' => $set->id,
                'enchant_set_total' => 100,
                'enchant_set_completed' => 75,
                'outcome_totals' => ['enchanted' => 75],
            ],
            'action_log' => [[
                'ts' => now()->toJSON(),
                'status' => 'enchanted',
                'enchanted_item' => ['name' => 'Capped Enchant Log Item', 'can_view' => false],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(75, $status['batch']['counts']['enchanted'] ?? null);
    }

    public function testStatusUsesProgressDisenchantedTotalInsteadOfCappedActionLog(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => [
                'craft_mode' => 'experience',
                'outcome_totals' => ['disenchanted' => 64],
            ],
            'action_log' => [[
                'ts' => now()->toJSON(),
                'status' => 'disenchanted',
                'disenchanted_item' => ['name' => 'Capped Disenchant Log Item', 'can_view' => false],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(64, $status['batch']['counts']['disenchanted'] ?? null);
    }

    public function testAlchemyExperienceStatusExposesAlchemySnapshotsAndFullRunTotals(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'progress' => [
                'alchemy_mode' => 'experience',
                'outcome_totals' => ['alchemy_processed' => 17],
                'alchemy_item_snapshots' => [[
                    'display_name' => 'Full Run Alchemy Snapshot',
                    'quantity' => 1,
                    'snapshot' => ['name' => 'Full Run Alchemy Snapshot', 'can_view' => false],
                    'slot_id' => null,
                    'crafted_at' => now()->toJSON(),
                ]],
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(17, $status['batch']['counts']['alchemy_processed'] ?? null);
        $this->assertSame('Full Run Alchemy Snapshot', $status['batch']['alchemy_item_snapshots'][0]['display_name'] ?? null);
    }

    public function testTrinketryStatusExposesTrinketrySnapshotsAndFullRunTotals(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['shards' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'progress' => [
                'trinketry_mode' => 'experience',
                'outcome_totals' => ['trinketry_processed' => 11],
                'trinketry_item_snapshots' => [[
                    'display_name' => 'Full Run Trinketry Snapshot',
                    'quantity' => 1,
                    'snapshot' => ['name' => 'Full Run Trinketry Snapshot', 'can_view' => false],
                    'slot_id' => null,
                    'crafted_at' => now()->toJSON(),
                ]],
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(11, $status['batch']['counts']['trinketry_processed'] ?? null);
        $this->assertSame('Full Run Trinketry Snapshot', $status['batch']['trinketry_item_snapshots'][0]['display_name'] ?? null);
    }

    public function testHolyOilsSelectedGearStatusExposesTargetItemSnapshots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'progress' => [
                'holy_oil_mode' => 'selected',
                'holy_oil_target_item_snapshots' => [[
                    'display_name' => 'Holy Oil Target Snapshot',
                    'quantity' => 1,
                    'snapshot' => ['name' => 'Holy Oil Target Snapshot', 'can_view' => false],
                    'slot_id' => null,
                    'crafted_at' => now()->toJSON(),
                ]],
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Holy Oil Target Snapshot', $status['batch']['holy_oil_target_item_snapshots'][0]['display_name'] ?? null);
    }

    public function testChartSuccessCountsEnchantedActions(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Chart Enchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Chart Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(2, $status['batch']['chart_points']['outcomes'][0]['success'] ?? null);
    }

    public function testChartSuccessCountsDisenchantedActions(): void
    {
        Bus::fake();
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Chart Disenchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Chart Disenchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(2, $status['batch']['chart_points']['outcomes'][0]['success'] ?? null);
    }

    public function testEnchantSetRequestedRemainingAndProgressUseEligibleTotal(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'progress' => [
                'enchant_mode' => 'set',
                'enchant_set_total' => 2,
                'enchant_set_completed' => 1,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(2, $status['batch']['requested_amount'] ?? null);
        $this->assertSame(1, $status['batch']['remaining_amount'] ?? null);
        $this->assertSame(50, $status['batch']['progress_percent'] ?? null);
    }

    public function testEnchantSetStatusExposesActualEnchantedItemSnapshots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'progress' => ['enchant_mode' => 'set'],
            'action_log' => [[
                'ts' => now()->toJSON(),
                'status' => 'enchanted',
                'enchanted_item' => ['name' => 'Actual Enchant Set Snapshot', 'can_view' => false],
            ]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Actual Enchant Set Snapshot', $status['batch']['enchanted_item_snapshots'][0]['display_name'] ?? null);
    }

    public function testCraftForEventStatusExposesRealCraftingXpGained(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 1, false, ['xp' => 0, 'xp_max' => 100, 'skill_bonus' => 1.0])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $this->createItem(['name' => 'Event XP Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertGreaterThan(0, $status['batch']['event_crafting_xp_gained'] ?? 0);
    }

    public function testEnchantForEventStatusExposesRealEnchantingXpGained(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 1, 'xp' => 0, 'xp_max' => 100, 'skill_bonus' => 1.0]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Event XP Enchant Target', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Event XP Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertGreaterThan(0, $status['batch']['event_enchanting_xp_gained'] ?? 0);
    }

    public function testCraftAndEnchantForExperienceStartsWhenAllCraftingSkillsMaxedButEnchantingIsNot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
    }

    public function testCraftAndEnchantForExperienceStartsWhenEnchantingMaxedButACraftingSkillIsNot(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
    }

    public function testCraftAndEnchantForExperienceRejectedWhenAllCraftingSkillsAndEnchantingAreMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 5, false)
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
    }

    public function testCraftAndEnchantExperienceProcessingContinuesWhenEnchantingMaxedButCraftingSkillIsNot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Enchant Maxed Still Crafts Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotSame(BatchCraftingEndReason::SKILL_MAXED->value, $result->ended_reason);
        $this->assertGreaterThan(0, $result->crafted_count);
    }

    public function testCraftAndEnchantAmountRejectsPrefixAboveCurrentEnchantingLevel(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 1, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Prefix Too High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Too High Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 50]);
        $suffix = $this->createItemAffix(['name' => 'Valid Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id, $suffix->id]],
        ]);
    }

    public function testCraftAndEnchantAmountRejectsSuffixAboveCurrentEnchantingLevel(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 1, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Suffix Too High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Valid Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Too High Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 50]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id, $suffix->id]],
        ]);
    }

    public function testCraftAndEnchantSetDoesNotUseSelectedSetOwnership(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Ownership Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Ownership Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $otherSet->id, 'enchant_plan' => $plan],
        ]);

        $this->assertArrayNotHasKey('selected_set_id', $batchCrafting->progress ?? []);
    }

    public function testCraftAndEnchantSetAcceptsSuffixOnlyPlan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $suffix = $this->createItemAffix(['name' => 'Suffix Only Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => null, 'suffix_affix_id' => $suffix->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertSame('running', $batchCrafting->status);
    }

    public function testCraftAndEnchantSetAcceptsPrefixOnlyPlan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Prefix Only Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertSame('running', $batchCrafting->status);
    }

    public function testCraftAndEnchantSetRejectsRowWithNeitherPrefixNorSuffix(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Neither Row Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Neither Row Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);
        $plan[$keys[0]] = ['prefix_affix_id' => null, 'suffix_affix_id' => null];

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testCraftAndEnchantSetRejectsSuffixAffixIdInPrefixSlot(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $suffix = $this->createItemAffix(['name' => 'Wrong Type For Prefix Slot', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $suffix->id, 'suffix_affix_id' => null]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testCraftAndEnchantSetRejectsPrefixAffixIdInSuffixSlot(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Wrong Type For Suffix Slot', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => null, 'suffix_affix_id' => $prefix->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testCraftAndEnchantSetPreviewCostIncludesPrefixOnlyCost(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Prefix Only Cost Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Prefix Only Cost Prefix', 'type' => 'prefix', 'cost' => 40, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null],
                ],
            ],
        ]);

        $daggerEntry = collect($preview['cost_breakdown']['plan_entries'])->firstWhere('key', 'dagger');

        $this->assertSame(40, $daggerEntry['prefix_cost'] ?? null);
        $this->assertSame(0, $daggerEntry['suffix_cost'] ?? null);
        $this->assertSame(1, $preview['cost_breakdown']['configured_items']);
        $this->assertSame(40, $preview['cost_breakdown']['enchant_cost_total']);
    }

    public function testCraftAndEnchantSetPreviewCostIncludesSuffixOnlyCost(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Suffix Only Cost Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Suffix Only Cost Suffix', 'type' => 'suffix', 'cost' => 60, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => null, 'suffix_affix_id' => $suffix->id],
                ],
            ],
        ]);

        $daggerEntry = collect($preview['cost_breakdown']['plan_entries'])->firstWhere('key', 'dagger');

        $this->assertSame(0, $daggerEntry['prefix_cost'] ?? null);
        $this->assertSame(60, $daggerEntry['suffix_cost'] ?? null);
        $this->assertSame(1, $preview['cost_breakdown']['configured_items']);
        $this->assertSame(60, $preview['cost_breakdown']['enchant_cost_total']);
    }

    public function testCraftSetStartBlockerUsesCraftSetPlanNotEnchantPlanForSelectedHighCostItems(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 10, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $cheapDagger = $this->createItem(['name' => 'Cheap Craft Set Plan Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItem(['name' => 'Expensive Craft Set Plan Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1000, 'skill_level_required' => 5, 'skill_level_trivial' => 400]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_plan' => [
                    'dagger' => ['selected_item_id' => $cheapDagger->id],
                ],
            ],
        ]);

        $this->assertSame('running', $batchCrafting->status);
    }

    public function testCraftAndEnchantSetIntBlockerNamesExactOffendingEnchantWithIntNumbers(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Ring Lords Curse', 'type' => 'prefix', 'cost' => 1, 'int_required' => 120, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => null, 'suffix_affix_id' => null]);
        $plan['ring_0'] = ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null];

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'int_too_low_for_enchanting');
        $characterInt = $character->getInformation()->statMod('int');

        $this->assertNotNull($blocker);
        $this->assertSame('ring_0', $blocker['plan_key'] ?? null);
        $this->assertSame($prefix->id, $blocker['affix_id'] ?? null);
        $this->assertSame('prefix', $blocker['affix_type'] ?? null);
        $this->assertSame(120, $blocker['int_required'] ?? null);
        $this->assertSame($characterInt, $blocker['character_int'] ?? null);
        $this->assertStringContainsString('Ring Lords Curse', $blocker['message']);
        $this->assertStringContainsString('120', $blocker['message']);
        $this->assertStringContainsString((string) $characterInt, $blocker['message']);
    }

    public function testCraftEnchantSetProcessorDoesNotAutoSelectAffixForEmptySelectedPlanRow(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'No Affix Selected Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $this->createItemAffix(['name' => 'Fallback Auto Affix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => null, 'suffix_affix_id' => null],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 1,
            ],
        ]);

        $goldBefore = (int) $character->gold;

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $entry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant');

        $this->assertSame('stopped', $entry['status'] ?? null);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertNull($item->refresh()->item_prefix_id);
        $this->assertNull($item->refresh()->item_suffix_id);
        $this->assertSame($goldBefore, (int) $character->refresh()->gold);
    }

    public function testCraftAndEnchantSetRejectsPrefixAffixAboveCurrentEnchantingLevel(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 1, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Too High Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 50]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Valid Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testCraftAndEnchantSetStartsWithValidFullEnchantPlan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Start Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Start Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertSame('craft_enchant_set', $batchCrafting->progress['craft_mode'] ?? null);
        $this->assertSame(count($keys) * 3, $batchCrafting->progress['craft_enchant_set_total_work_units'] ?? null);
        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftAndEnchantSetAllowsSellDisposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Disposition Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Disposition Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertSame(BatchCraftingDisposition::SELL->value, $batchCrafting->disposition);
    }

    public function testCraftAndEnchantSetRejectsKeepBestSellRestDisposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Keep Best Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Keep Best Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testCraftAndEnchantSetCraftsAllPlannedItemsBeforeApplyingEnchants(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Enchant Set Order Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Craft Enchant Set Order Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Order Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Order Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger'], ['type' => 'sword', 'crafting_type' => 'sword']],
                'craft_enchant_set_keys' => ['dagger', 'sword'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                    'sword' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 2,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 6,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $actionTypes = collect($result->action_log)->pluck('action_type')->values()->all();
        $craftIndex = array_search('craft_enchant_set_craft', $actionTypes);
        $enchantIndex = array_search('craft_enchant_set_enchant', $actionTypes);

        $this->assertNotFalse($craftIndex, 'no craft action recorded: ' . json_encode($result->action_log));
        $this->assertNotFalse($enchantIndex, 'no enchant action recorded: ' . json_encode($result->action_log));
        $this->assertLessThan($enchantIndex, $craftIndex);
    }

    public function testCraftAndEnchantSetPlacesCompletedFinalItemsIntoCraftedItemsSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Enchant Set Finalize Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Finalize Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Finalize Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $result = resolve(BatchCraftingService::class)->process($batchCrafting->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($craftedItemsSet);
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame(1, $result->progress['craft_enchant_set_completed_final_count'] ?? null, $result->ended_reason ?? 'no end reason');
    }

    public function testCraftAndEnchantSetCompletionSummaryReportsFullSetCompleted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_completed_final_count' => 1,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('all', $status['batch']['completion_summary'] ?? null);
        $batchCrafting->delete();
    }

    public function testCraftAndEnchantSetStartsWithOneMinutePendingTimer(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Timer Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Timer Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testGoldDustGainedOverTimeChartDataIsRecordedWhenDispositionDisenchants(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Gold Dust Chart Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Gold Dust Chart Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $goldDustPoints = $result->progress['chart_points']['gold_dust'] ?? [];

        $this->assertNotEmpty($goldDustPoints);
        $this->assertGreaterThan(0, $goldDustPoints[0]['gained'] ?? 0, $result->ended_reason ?? 'no end reason');
    }

    public function testCraftAndEnchantSetStatusExposesCurrentPrefixAndSuffixWhenEnchanting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Current Prefix Suffix Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Current Prefix Affix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Current Suffix Affix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertSame('Current Prefix Affix', $status['batch']['craft_enchant_set_current_prefix'] ?? null);
        $this->assertSame('Current Suffix Affix', $status['batch']['craft_enchant_set_current_suffix'] ?? null);
    }

    public function testCraftAndEnchantSetActionLogIncludesPhase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Phase Log Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Phase Log Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Phase Log Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $phases = collect($result->action_log)->pluck('phase')->filter()->values()->all();

        $this->assertContains('crafting', $phases);
        $this->assertContains('enchanting', $phases);
    }

    public function testCraftAndEnchantSetActionLogIncludesPrefixAndSuffixWhenApplicable(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Prefix Suffix Log Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Prefix Suffix Log Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Prefix Suffix Log Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantEntry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant');

        $this->assertSame('Prefix Suffix Log Prefix', $enchantEntry['prefix_affix_name'] ?? null);
        $this->assertSame('Prefix Suffix Log Suffix', $enchantEntry['suffix_affix_name'] ?? null);
        $this->assertTrue($enchantEntry['prefix_applied'] ?? false);
        $this->assertTrue($enchantEntry['suffix_applied'] ?? false);
    }

    public function testCraftAndEnchantSetLogsDoubleEnchantedStatusWhenBothAffixesApply(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Double Enchant Log Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Double Enchant Log Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Double Enchant Log Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantEntry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant');

        $this->assertSame('double_enchanted', $enchantEntry['status'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_set_prefix_applied_count'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_set_suffix_applied_count'] ?? null);
    }

    public function testCraftAndEnchantSetActionLogIncludesGoldSpentForCraftAction(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Gold Spent Log Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 25, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Gold Spent Log Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Gold Spent Log Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftEntry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_craft');

        $this->assertIsInt($craftEntry['gold_spent'] ?? null);
        $this->assertGreaterThan(0, $craftEntry['gold_spent'] ?? 0);
    }

    public function testCraftAndEnchantSetActionLogIncludesDestinationSetWhenMovedToSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Moved To Set Log Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Moved To Set Log Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $finalizeEntry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_finalize');
        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();

        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $finalizeEntry['destination_set'] ?? null);
        $this->assertTrue($finalizeEntry['created_in_crafted_items_set'] ?? false);
        $this->assertSame(1, $craftedItemsSet?->slots()->count());
        $this->assertNotNull($finalizeEntry['kept_item']['set_slot_id'] ?? null);
    }

    public function testCraftAndEnchantSetFinalizeSellDispositionRemovesFinalOutputFromCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Finalize Sell Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Finalize Sell Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->sold_count);
        $this->assertGreaterThan(0, collect($result->action_log)->sum('gold_gained'));
    }

    public function testCraftAndEnchantSetFinalizeDestroyDispositionRemovesFinalOutputFromCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Finalize Destroy Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Finalize Destroy Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->destroyed_count);
    }

    public function testCraftAndEnchantSetFinalizeListDispositionCreatesMarketRowAndNoCraftedItemsSetOutput(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Finalize List Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Finalize List Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'listing_price' => 888,
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->listed_count);
        $this->assertSame(1, MarketBoard::where('character_id', $character->id)->where('item_id', $item->id)->count());
        $this->assertGreaterThanOrEqual(888, MarketBoard::where('character_id', $character->id)->first()->listed_price);
    }

    public function testCraftAndEnchantSetFinalizeDisenchantDispositionRemovesFinalOutputFromCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Finalize Disenchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Finalize Disenchant Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->progress['outcome_totals']['disenchanted'] ?? null);
        $this->assertGreaterThan(0, collect($result->action_log)->sum('gold_dust_gained'));
    }

    public function testCraftForExperienceStatusExposesCraftedItemsSetMaxSlotsAsTwoThousand(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(2000, $status['batch']['batch_crafting_set']['max_slots'] ?? null);
    }

    public function testCraftAndEnchantAmountKeepDispositionExposesNormalInventoryCapacityData(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 25]);
        $item = $this->createItem(['name' => 'Inventory Capacity Data Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(25, $status['batch']['inventory_max'] ?? null);
        $this->assertIsInt($status['batch']['inventory_count'] ?? null);
    }

    public function testCraftAndEnchantAmountSellDispositionDoesNotMoveOutputToCraftedItemsSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Sell Disposition Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Sell Disposition Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
    }

    public function testCraftSetIgnoresSelectedSetIdSinceDestinationIsAlwaysCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);
        $prefix = $this->createItemAffix(['name' => 'Ignored Crafted Items Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $craftedItemsSet->id],
        ]);

        $this->assertSame('running', $batchCrafting->status);
    }

    public function testCraftEnchantSetIgnoresCraftedItemsSetAsSelectedSetBecauseDestinationIsAlwaysCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);
        $prefix = $this->createItemAffix(['name' => 'Ignored Crafted Items Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $craftedItemsSet->id, 'enchant_plan' => $plan],
        ]);

        $this->assertArrayNotHasKey('selected_set_id', $batchCrafting->progress ?? []);
        $this->assertSame('craft_new', $batchCrafting->progress['craft_enchant_set_target_mode'] ?? null);
    }

    public function testHolyOilsSetRejectsCraftedItemsSetAsDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $craftedItemsSet->id],
        ]);
    }

    public function testCraftSetDoesNotRequireAnEmptySelectedSetSinceNoSetIsNeeded(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);
        $prefix = $this->createItemAffix(['name' => 'Ignored Non Empty Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $set->id],
        ]);

        $this->assertSame('running', $batchCrafting->status);
    }

    public function testCraftEnchantSetIgnoresNonEmptySelectedSetBecauseDestinationIsAlwaysCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);
        $prefix = $this->createItemAffix(['name' => 'Ignored Non Empty Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertArrayNotHasKey('selected_set_id', $batchCrafting->progress ?? []);
    }

    public function testCraftEnchantSetDestroyedEnchantRecordsHonestOutcomeWithoutClaimingAffixes(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Destroyed Enchant Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $prefix = $this->createItemAffix(['name' => 'Destroyed Enchant Set Prefix', 'type' => 'prefix', 'cost' => 25, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Destroyed Enchant Set Suffix', 'type' => 'suffix', 'cost' => 25, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                // Invalid id: any replacement-craft attempt made later in this same
                // tick (after the shatter switches the phase) is a harmless no-op skip
                // instead of a real craft, so this test's outcome is fully deterministic.
                'craft_enchant_set_selected_item_ids' => ['dagger' => 999999999],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 1,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'craft_enchant_set_replacement_key' => null,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $entry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant');

        $this->assertSame('destroyed', $entry['status'] ?? null);
        $this->assertSame('Destroyed Enchant Set Dagger', $entry['destroyed_item']['name'] ?? null);
        $this->assertFalse($entry['destroyed_item']['can_view'] ?? true);
        $this->assertFalse($entry['prefix_applied'] ?? true);
        $this->assertFalse($entry['suffix_applied'] ?? true);
        $this->assertIsInt($entry['gold_spent'] ?? null);
        $this->assertGreaterThan(0, $entry['gold_spent'] ?? 0);
        $this->assertSame(1, $result->destroyed_count);
        $this->assertSame(0, $result->fresh()->progress['craft_enchant_set_enchant_index'] ?? null);
        $this->assertSame(0, $result->fresh()->progress['craft_enchant_set_completed_work_units'] ?? null);
        $this->assertSame(0, $result->fresh()->progress['craft_enchant_set_surviving_crafted_count'] ?? null);
        $this->assertSame('replacement_crafting', $result->fresh()->progress['craft_enchant_set_phase'] ?? null);
        $this->assertNull($item->refresh()->item_prefix_id);
    }

    public function testCraftAndEnchantAmountDestroyedEnchantRecordsHonestOutcome(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Destroyed Amount Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Destroyed Amount Prefix', 'type' => 'prefix', 'cost' => 25, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $destroyedEntry = collect($result->action_log)->first(fn (array $entry) => ($entry['status'] ?? null) === 'destroyed');

        $this->assertNotNull($destroyedEntry);
        $this->assertSame('Destroyed Amount Sword', $destroyedEntry['destroyed_item']['name'] ?? null);
        $this->assertFalse($destroyedEntry['destroyed_item']['can_view'] ?? true);
        $this->assertSame(1, $result->destroyed_count);
        $this->assertSame(0, $result->kept_count);
    }

    public function testActionLogIsNotTrimmedPastFiftyEntries(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $existingLog = [];

        for ($i = 0; $i < 55; $i++) {
            $existingLog[] = ['ts' => now()->toJSON(), 'action_type' => 'craft', 'status' => 'skipped'];
        }

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'action_log' => $existingLog,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(50, count($result->action_log));
    }

    public function testCraftAmountStatusExposesCraftedItemsSetDestinationAndCostPreview(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Amount Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'cost' => 25]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 4],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['amount_preview'];

        $this->assertSame('crafted_items_set', $preview['destination']);
        $this->assertSame(25, $preview['per_item_cost']);
        $this->assertSame(100, $preview['total_cost']);
        $this->assertSame(4, $preview['effective_craftable_amount']);
        $this->assertFalse($preview['capped']);
    }

    public function testCraftAndEnchantAmountStatusExposesEnchantRiskAndCostPreview(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Amount Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);
        $prefix = $this->createItemAffix(['name' => 'Amount Preview Prefix', 'type' => 'prefix', 'cost' => 15, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['amount_preview'];

        $this->assertTrue($preview['enchant_can_destroy_item']);
        $this->assertSame('Amount Preview Prefix', $preview['prefix_affix_name']);
        $this->assertSame(25, $preview['total_per_item_cost']);
    }

    public function testCraftAndEnchantAmountPreviewExposesFailureRiskBelowLevelFourHundred(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill?->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Risk Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);
        $prefix = $this->createItemAffix(['name' => 'Risk Preview Prefix', 'type' => 'prefix', 'cost' => 15, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['amount_preview'];

        $this->assertTrue($preview['enchant_has_failure_risk']);
    }

    public function testCraftAndEnchantAmountPreviewHidesFailureRiskAtLevelFourHundred(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill?->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 400]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'No Risk Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);
        $prefix = $this->createItemAffix(['name' => 'No Risk Preview Prefix', 'type' => 'prefix', 'cost' => 15, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['amount_preview'];

        $this->assertFalse($preview['enchant_has_failure_risk']);
    }

    public function testCraftAndEnchantAmountPreviewWorksWithNoAffixesSelected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'No Affix Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => []],
        ]);

        $this->assertNull($preview['amount_preview']['prefix_affix_name']);
        $this->assertNull($preview['amount_preview']['suffix_affix_name']);
        $this->assertSame(10, $preview['amount_preview']['total_per_item_cost']);
    }

    public function testCraftAndEnchantAmountPreviewWorksWithPrefixOnly(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Prefix Only Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);
        $prefix = $this->createItemAffix(['name' => 'Prefix Only Preview Prefix', 'type' => 'prefix', 'cost' => 15, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $this->assertSame('Prefix Only Preview Prefix', $preview['amount_preview']['prefix_affix_name']);
        $this->assertNull($preview['amount_preview']['suffix_affix_name']);
        $this->assertSame(25, $preview['amount_preview']['total_per_item_cost']);
    }

    public function testCraftAndEnchantAmountPreviewWorksWithSuffixOnly(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Suffix Only Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);
        $suffix = $this->createItemAffix(['name' => 'Suffix Only Preview Suffix', 'type' => 'suffix', 'cost' => 20, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$suffix->id]],
        ]);

        $this->assertNull($preview['amount_preview']['prefix_affix_name']);
        $this->assertSame('Suffix Only Preview Suffix', $preview['amount_preview']['suffix_affix_name']);
        $this->assertSame(30, $preview['amount_preview']['total_per_item_cost']);
    }

    public function testCraftAndEnchantAmountPreviewWorksWithBothAffixesSelected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Both Affix Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'cost' => 10]);
        $prefix = $this->createItemAffix(['name' => 'Both Affix Preview Prefix', 'type' => 'prefix', 'cost' => 15, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Both Affix Preview Suffix', 'type' => 'suffix', 'cost' => 20, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$prefix->id, $suffix->id]],
        ]);

        $this->assertSame('Both Affix Preview Prefix', $preview['amount_preview']['prefix_affix_name']);
        $this->assertSame('Both Affix Preview Suffix', $preview['amount_preview']['suffix_affix_name']);
        $this->assertSame(45, $preview['amount_preview']['total_per_item_cost']);
    }

    public function testCraftAndEnchantAmountStartRejectsMissingRequiredAffixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Missing Affix Start Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => []],
        ]);
    }

    public function testHolyOilsSelectedPreviewTreatsIdenticalNameItemsAsSeparateSelections(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Duplicate Rusty Shiv', 'type' => 'weapon', 'holy_stacks' => 3]);
        $firstSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $secondSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Duplicate Preview Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 5]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$firstSlot->id, $secondSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['holy_oil_selected_preview'];

        $this->assertSame(2, $preview['total_eligible_items']);
        $this->assertSame(6, $preview['total_remaining_applications']);
    }

    public function testHolyOilsProcessingAppliesOnlyToSelectedOwnedSlot(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Processed Rusty Shiv', 'type' => 'weapon', 'holy_stacks' => 3]);
        $selectedSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $untouchedSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Processed Preview Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$selectedSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $untouchedSlotAfterProcessing = InventorySlot::find($untouchedSlot->id);

        $this->assertNotNull($untouchedSlotAfterProcessing);
        $this->assertSame(0, $untouchedSlotAfterProcessing->item->holy_stacks_applied);
    }

    public function testBatchHolyOilsSelectedDestroyEmitsDestroyedServerMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Holy Oil Selected Destroy Weapon', 'type' => 'weapon', 'holy_stacks' => 1]);
        $selectedSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Oil Selected Destroy Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'selected_items' => [$selectedSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_starts_with($event->message, 'Destroyed:'));
    }

    public function testBatchHolyOilsSelectedListEmitsListedServerMessageWithPrice(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Holy Oil Selected List Weapon', 'type' => 'weapon', 'holy_stacks' => 1, 'cost' => 100]);
        $selectedSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Oil Selected List Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'selected_items' => [$selectedSlot->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => ['listing_price' => 50],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_starts_with($event->message, 'Listed:') && str_contains($event->message, 'Gold.'));
    }

    public function testBatchHolyOilsSetDestroyEmitsDestroyedServerMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Holy Oil Set Destroy Weapon', 'type' => 'weapon', 'holy_stacks' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Oil Set Destroy Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id, 'holy_oil_total_stacks' => 1, 'holy_oil_requested_applications' => 1, 'holy_oil_completed_applications' => 0],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_starts_with($event->message, 'Destroyed:'));
    }

    public function testBatchHolyOilsSetListEmitsListedServerMessageWithPrice(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Holy Oil Set List Weapon', 'type' => 'weapon', 'holy_stacks' => 1, 'cost' => 100]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Oil Set List Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id, 'holy_oil_total_stacks' => 1, 'holy_oil_requested_applications' => 1, 'holy_oil_completed_applications' => 0, 'listing_price' => 50],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_starts_with($event->message, 'Listed:') && str_contains($event->message, 'Gold.'));
    }

    public function testAlchemyAmountStatusExposesBagCapAndCostPreview(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Amount Preview Potion', 'type' => 'alchemy', 'gold_dust_cost' => 50, 'shards_cost' => 0]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $item->id, 'alchemy_amount' => 3],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['alchemy_amount_preview'];

        $this->assertSame(50, $preview['gold_dust_cost_per_item']);
        $this->assertSame(150, $preview['total_gold_dust_cost']);
        $this->assertIsInt($preview['bag_max']);
        $this->assertSame(3, $preview['effective_craftable_amount']);
    }

    public function testHolyOilsSelectedPreviewExposesStacksRemainingCostAndCapped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Holy Preview Sword', 'type' => 'weapon', 'holy_stacks' => 3]);
        HolyStack::create(['item_id' => $item->id, 'devouring_darkness_bonus' => 0, 'stat_increase_bonus' => 0]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Preview Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['holy_oil_selected_preview'];

        $this->assertSame(1, $preview['items'][0]['current_stacks']);
        $this->assertSame(3, $preview['items'][0]['max_stacks']);
        $this->assertSame(2, $preview['items'][0]['remaining_capacity']);
        $this->assertSame(1, $preview['selected_oils_available']);
        $this->assertSame(1, $preview['max_applications_possible']);
        $this->assertTrue($preview['capped']);
    }

    public function testHolyOilsSetPreviewExposesPerItemStacksTotalRemainingCostAndCapped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $item = $this->createItem(['name' => 'Holy Set Preview Sword', 'type' => 'weapon', 'holy_stacks' => 2]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Set Preview Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'set', 'selected_set_id' => $set->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $preview = $status['batch']['holy_oil_set_preview'];

        $this->assertSame(1, $preview['total_eligible_items']);
        $this->assertSame(2, $preview['total_remaining_applications']);
        $this->assertSame(1, $preview['max_applications_possible']);
        $this->assertTrue($preview['capped']);
        $this->assertNotNull($preview['capped_message']);
    }

    public function testCraftAmountAllowsFullRequestWhenCraftedItemsSetHasExactlyEnoughRemainingSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Near Full Cap Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $craftedItemsSet = InventorySet::create([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => 3,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 3, 'craft_specific_count' => 0],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(3, $result->kept_count, $result->ended_reason ?? 'no end reason');
        $this->assertSame(3, $craftedItemsSet->refresh()->slots()->count());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testCraftAmountCapsAtRemainingCraftedItemsSetSpaceWhenRequestExceedsIt(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Over Cap Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $craftedItemsSet = InventorySet::create([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => 2,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(2, $result->kept_count);
        $this->assertSame(2, $craftedItemsSet->refresh()->slots()->count());
        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testCraftAndEnchantAmountAllowsFullRequestWhenCraftedItemsSetHasExactlyEnoughRemainingSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Near Full Cap Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Near Full Cap Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $craftedItemsSet = InventorySet::create([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => 2,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 2, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(2, $craftedItemsSet->refresh()->slots()->count(), $result->ended_reason ?? 'no end reason');
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testCraftAndEnchantAmountCapsAtRemainingCraftedItemsSetSpaceWhenRequestExceedsIt(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Over Cap Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Over Cap Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $craftedItemsSet = InventorySet::create([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => 1,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 3, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $craftedItemsSet->refresh()->slots()->count());
        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testStartSendsServerMessageStatingFirstActionRunsInOneMinute(): void
    {
        Event::fake([ServerMessageEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        Event::assertDispatched(ServerMessageEvent::class, function ($event) {
            return $event->message === 'Batch crafting has started. First action will run in 1 minute.';
        });
    }

    public function testCraftEnchantSetFinalizePhaseDoesNotMoveDestroyedItemIntoDestinationSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => 999999],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertNull($result->fresh()->progress['craft_enchant_set_completed_final_count'] ?? null);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->fresh()->progress['craft_enchant_set_finalize_index'] ?? null);
    }

    public function testBrandNewCharacterStatusPayloadShowsCraftForExperienceAvailable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertTrue($status['craft_mode_availability']['can_craft_for_experience']);
    }

    public function testBrandNewCharacterStatusPayloadShowsCraftAndEnchantForExperienceAvailable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertTrue($status['craft_mode_availability']['can_craft_and_enchant_for_experience']);
    }

    public function testCraftForExperienceIsAvailableWhenAnyRelevantCraftingSkillCanGainXp(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCraftingBase = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->assignSkill($spellCraftingBase, 5, false)
            ->getCharacter();
        $armourCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Armour Crafting');
        $ringCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Ring Crafting');
        $spellCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Spell Crafting');
        $armourCrafting->update(['level' => $armourCrafting->max_level]);
        $ringCrafting->update(['level' => $ringCrafting->max_level]);
        $spellCrafting->update(['level' => $spellCrafting->max_level]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['craft_mode_availability']['can_craft_for_experience']);
    }

    public function testCraftForExperienceIsAvailableWhenACraftingSkillRowIsMissingEntirely(): void
    {
        $weaponCraftingBase = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCraftingBase, 5, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->getCharacter();
        $weaponCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Weapon Crafting');
        $armourCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Armour Crafting');
        $ringCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Ring Crafting');
        $weaponCrafting->update(['level' => $weaponCrafting->max_level]);
        $armourCrafting->update(['level' => $armourCrafting->max_level]);
        $ringCrafting->update(['level' => $ringCrafting->max_level]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['craft_mode_availability']['can_craft_for_experience']);
    }

    public function testCraftForExperienceIsHiddenOnlyWhenAllRelevantCraftingSkillsAreMaxed(): void
    {
        $weaponCraftingBase = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCraftingBase = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCraftingBase, 5, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->assignSkill($spellCraftingBase, 5, false)
            ->getCharacter();
        $weaponCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Weapon Crafting');
        $armourCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Armour Crafting');
        $ringCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Ring Crafting');
        $spellCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Spell Crafting');
        $weaponCrafting->update(['level' => $weaponCrafting->max_level]);
        $armourCrafting->update(['level' => $armourCrafting->max_level]);
        $ringCrafting->update(['level' => $ringCrafting->max_level]);
        $spellCrafting->update(['level' => $spellCrafting->max_level]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertFalse($status['craft_mode_availability']['can_craft_for_experience']);
    }

    public function testCraftAndEnchantForExperienceIsAvailableWhenCraftingCanGainXp(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false)
            ->getCharacter();

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertTrue($status['craft_mode_availability']['can_craft_and_enchant_for_experience']);
    }

    public function testCraftAndEnchantForExperienceIsAvailableWhenOnlyEnchantingCanGainXp(): void
    {
        $weaponCraftingBase = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCraftingBase = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCraftingBase, 5, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->assignSkill($spellCraftingBase, 5, false)
            ->getCharacter();
        $weaponCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Weapon Crafting');
        $armourCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Armour Crafting');
        $ringCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Ring Crafting');
        $spellCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Spell Crafting');
        $weaponCrafting->update(['level' => $weaponCrafting->max_level]);
        $armourCrafting->update(['level' => $armourCrafting->max_level]);
        $ringCrafting->update(['level' => $ringCrafting->max_level]);
        $spellCrafting->update(['level' => $spellCrafting->max_level]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['craft_mode_availability']['can_craft_and_enchant_for_experience']);
    }

    public function testCraftAndEnchantForExperienceIsAvailableWhenEnchantingSkillRowIsMissingEntirely(): void
    {
        $weaponCraftingBase = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCraftingBase = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [], false)
            ->assignSkill($weaponCraftingBase, 5, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->assignSkill($spellCraftingBase, 5, false)
            ->getCharacter();

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['craft_mode_availability']['can_craft_and_enchant_for_experience']);
    }

    public function testCraftAndEnchantForExperienceIsHiddenOnlyWhenCraftingAndEnchantingAreMaxed(): void
    {
        $weaponCraftingBase = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCraftingBase = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCraftingBase, 5, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->assignSkill($spellCraftingBase, 5, false)
            ->getCharacter();
        $weaponCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Weapon Crafting');
        $armourCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Armour Crafting');
        $ringCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Ring Crafting');
        $spellCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Spell Crafting');
        $enchanting = $character->skills->first(fn ($skill) => $skill->baseSkill?->type === SkillTypeValue::ENCHANTING->value);
        $weaponCrafting->update(['level' => $weaponCrafting->max_level]);
        $armourCrafting->update(['level' => $armourCrafting->max_level]);
        $ringCrafting->update(['level' => $ringCrafting->max_level]);
        $spellCrafting->update(['level' => $spellCrafting->max_level]);
        $enchanting->update(['level' => $enchanting->max_level]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertFalse($status['craft_mode_availability']['can_craft_and_enchant_for_experience']);
    }

    public function testDisenchantingDoesNotControlCraftAndEnchantForExperienceVisibility(): void
    {
        $weaponCraftingBase = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCraftingBase = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCraftingBase = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCraftingBase = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCraftingBase, 5, false)
            ->assignSkill($armourCraftingBase, 5, false)
            ->assignSkill($ringCraftingBase, 5, false)
            ->assignSkill($spellCraftingBase, 5, false)
            ->getCharacter();
        $weaponCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Weapon Crafting');
        $armourCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Armour Crafting');
        $ringCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Ring Crafting');
        $spellCrafting = $character->skills->first(fn ($skill) => $skill->baseSkill?->name === 'Spell Crafting');
        $disenchanting = $character->skills->first(fn ($skill) => $skill->baseSkill?->type === SkillTypeValue::DISENCHANTING->value);
        $weaponCrafting->update(['level' => $weaponCrafting->max_level]);
        $armourCrafting->update(['level' => $armourCrafting->max_level]);
        $ringCrafting->update(['level' => $ringCrafting->max_level]);
        $spellCrafting->update(['level' => $spellCrafting->max_level]);
        $disenchanting->update(['level' => $disenchanting->max_level]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['craft_mode_availability']['can_craft_and_enchant_for_experience']);
    }

    public function testCraftAndEnchantSetPreviewExposesFullPlanCostAndAffordability(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 50, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Set Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Set Preview Prefix', 'type' => 'prefix', 'cost' => 30, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Set Preview Suffix', 'type' => 'suffix', 'cost' => 40, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
            ],
        ]);

        $this->assertSame(20, $preview['cost_breakdown']['craft_cost_total']);
        $this->assertSame(70, $preview['cost_breakdown']['enchant_cost_total']);
        $this->assertSame(90, $preview['cost_breakdown']['total_required_gold']);
        $this->assertSame(50, $preview['cost_breakdown']['available_currency_amount']);
        $this->assertFalse($preview['cost_breakdown']['can_afford_full_plan']);
        $this->assertSame(40, $preview['cost_breakdown']['missing_currency_amount']);
    }

    public function testCraftAndEnchantSetPreviewHonorsSelectedItemOverrideInsteadOfHighest(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 500, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Override High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 50, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $lowDagger = $this->createItem(['name' => 'Override Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => [
                    'dagger' => ['selected_item_id' => $lowDagger->id],
                ],
            ],
        ]);

        $this->assertSame(10, $preview['cost_breakdown']['craft_cost_total']);
    }

    public function testCraftAndEnchantSetStartRejectsSelectedItemForWrongSlotType(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Wrong Slot Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $sword = $this->createItem(['name' => 'Wrong Slot Sword', 'type' => 'sword', 'crafting_type' => 'sword', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Wrong Slot Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Wrong Slot Suffix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $keys = resolve(BatchCraftingProcessor::class)->craftEnchantSetPlanKeys($queue);
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);
        $plan['dagger'] = array_merge($plan['dagger'], ['selected_item_id' => $sword->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => $plan,
            ],
        ]);
    }

    public function testCraftAndEnchantSetStartRejectsSelectedItemNotCraftableByCharacterSkillLevel(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $tooHighDagger = $this->createItem(['name' => 'Too High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 50, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['name' => 'Skill Level Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Skill Level Suffix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $keys = resolve(BatchCraftingProcessor::class)->craftEnchantSetPlanKeys($queue);
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);
        $plan['dagger'] = array_merge($plan['dagger'], ['selected_item_id' => $tooHighDagger->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => $plan,
            ],
        ]);
    }

    public function testCraftAndEnchantSetStartRejectsUnaffordableFullPlan(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 50, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Set Reject Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Set Reject Prefix', 'type' => 'prefix', 'cost' => 30, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Set Reject Suffix', 'type' => 'suffix', 'cost' => 40, 'int_required' => 0, 'skill_level_required' => 1]);
        $queue = resolve(BatchCraftingProcessor::class)->craftSetQueue();
        $keys = resolve(BatchCraftingProcessor::class)->craftEnchantSetPlanKeys($queue);
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You do not have enough Gold to start this batch. Required: 90, Available: 50, Missing: 40.');

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => $plan,
            ],
        ]);
    }

    public function testCraftSetPreviewExposesRequiredCostAndAffordability(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 10, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Craft Set Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 25, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $set->id],
        ]);

        $this->assertSame(25, $preview['cost_breakdown']['craft_cost_total']);
        $this->assertSame(25, $preview['cost_breakdown']['total_required']);
        $this->assertFalse($preview['cost_breakdown']['can_afford_start']);
    }

    public function testAlchemyMissingCurrencyPreviewIdentifiesRequiredCurrencyAndAmounts(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 0, 'inventory_max' => 30]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $this->assertSame('Gold Dust', $preview['cost_breakdown']['currency_label']);
        $this->assertSame(1, $preview['cost_breakdown']['required_to_start']);
        $this->assertSame(0, $preview['cost_breakdown']['available_currency_amount']);
        $this->assertStringContainsString('Gold Dust is awarded by the daily lottery and by disenchanting items.', $preview['cost_breakdown']['message']);
    }

    public function testTrinketryMissingCurrencyPreviewIdentifiesRequiredCurrencyAndAmounts(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['shards' => 0, 'inventory_max' => 30]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $this->assertSame('Shards', $preview['cost_breakdown']['currency_label']);
        $this->assertSame(1, $preview['cost_breakdown']['required_to_start']);
        $this->assertSame(0, $preview['cost_breakdown']['available_currency_amount']);
        $this->assertStringContainsString('Shards are awarded by battle rewards and by selling gems.', $preview['cost_breakdown']['message']);
    }

    public function testActiveFactionLoyaltyAutomationBlocksBatchCraftingStart(): void
    {
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($spellCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Batch crafting cannot start while faction loyalty automation is running.');

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
    }

    public function testActiveFactionLoyaltyAutomationBlocksCraftAmountStart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Batch crafting cannot start while faction loyalty automation is running.');

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item'],
        ]);
    }

    public function testActiveFactionLoyaltyAutomationBlocksCraftAndEnchantStart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Batch crafting cannot start while faction loyalty automation is running.');

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
    }

    public function testActiveFactionLoyaltyAutomationBlocksAlchemyStart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Batch crafting cannot start while faction loyalty automation is running.');

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);
    }

    public function testHistoricalFactionLoyaltyAutomationDoesNotBlockBatchCraftingStart(): void
    {
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($spellCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subHours(9),
            'completed_at' => now()->subHour(),
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
    }

    public function testCraftForExperienceFailureDoesNotCompleteBatch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'status' => 'failed', 'failure' => 'Craft failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertNull($batchCrafting->completed_at);
        $this->assertNull($batchCrafting->ended_reason);
    }

    public function testCraftAmountFailureDoesNotCompleteBatch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'specific_item'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'status' => 'failed', 'failure' => 'Craft failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertNull($batchCrafting->completed_at);
        $this->assertNull($batchCrafting->ended_reason);
    }

    public function testCraftAndEnchantFailureDoesNotCompleteBatch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft_and_enchant', 'status' => 'failed', 'failure' => 'Craft and enchant failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertNull($batchCrafting->completed_at);
        $this->assertNull($batchCrafting->ended_reason);
    }

    public function testAlchemyFailureDoesNotCompleteBatch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 100]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'alchemy', 'status' => 'failed', 'failure' => 'Alchemy failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertNull($batchCrafting->completed_at);
        $this->assertNull($batchCrafting->ended_reason);
    }

    public function testTrinketryFailureDoesNotCompleteBatch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['shards' => 100]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'trinketry', 'status' => 'failed', 'failure' => 'Trinketry failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertNull($batchCrafting->completed_at);
        $this->assertNull($batchCrafting->ended_reason);
    }

    public function testBatchCraftingSetFullHardStopStillCompletesBatch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn(['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $batchCrafting->refresh()->ended_reason);
        $this->assertNotNull($batchCrafting->completed_at);
    }

    public function testCraftForExperienceSkipsTooEasyItemAndCraftsNextEligibleItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 300, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Too Easy Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Eligible Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedTooEasyItem = collect($result->action_log)->contains(fn (array $entry) => ($entry['crafted_item']['name'] ?? null) === 'Too Easy Dagger');
        $craftedEligibleItem = collect($result->action_log)->contains(fn (array $entry) => ($entry['crafted_item']['name'] ?? null) === 'Eligible Sword');

        $this->assertFalse($craftedTooEasyItem);
        $this->assertTrue($craftedEligibleItem);
    }

    public function testCraftForExperienceStopsOnlyWhenNoXpEligibleCraftTargetsRemain(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 300, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Only Too Easy Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
    }

    public function testCraftAndEnchantForExperienceSkipsTooEasyCraftItemAndUsesNextEligibleItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 300, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'CE Too Easy Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'CE Eligible Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedTooEasyItem = collect($result->action_log)->contains(fn (array $entry) => ($entry['crafted_item']['name'] ?? null) === 'CE Too Easy Dagger');
        $craftedEligibleItem = collect($result->action_log)->contains(fn (array $entry) => ($entry['crafted_item']['name'] ?? null) === 'CE Eligible Sword');

        $this->assertFalse($craftedTooEasyItem);
        $this->assertTrue($craftedEligibleItem);
    }

    public function testCraftAndEnchantForExperienceSkipsTooEasyEnchantWorkAndUsesEligibleEnchantWork(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $enchanting = $this->createGameSkill(['name' => 'Enchanting', 'type' => SkillTypeValue::ENCHANTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 1, false)
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 300, 'game_skill_id' => $enchanting->id]);
        $character->update(['gold' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Enchant Eligibility Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Too Easy Affix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItemAffix(['name' => 'Eligible Affix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedWithTooEasyAffix = collect($result->action_log)->contains(fn (array $entry) => ($entry['enchanted_item']['item_prefix'] ?? null) === 'Too Easy Affix');
        $enchantedWithEligibleAffix = collect($result->action_log)->contains(fn (array $entry) => ($entry['enchanted_item']['item_prefix'] ?? null) === 'Eligible Affix');

        $this->assertFalse($enchantedWithTooEasyAffix);
        $this->assertTrue($enchantedWithEligibleAffix);
    }

    public function testCraftAndEnchantForExperienceDestroyedItemDoesNotCompleteBatch(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $enchanting = $this->createGameSkill(['name' => 'Enchanting', 'type' => SkillTypeValue::ENCHANTING->value, 'max_level' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) use ($enchanting) {
                $mock->shouldReceive('getDCCheck')->andReturnUsing(fn ($skill) => $skill->game_skill_id === $enchanting->id ? 1000 : 1);
                $mock->shouldReceive('characterRoll')->andReturnUsing(fn ($skill) => $skill->game_skill_id === $enchanting->id ? 1 : 400);
            })
        );
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 1, false)
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 1, 'game_skill_id' => $enchanting->id]);
        $character->update(['gold' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Experience Destroy Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Experience Destroy Affix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->destroyed_count);
        $this->assertNull($result->ended_reason);
        $this->assertNull($result->completed_at);
    }

    public function testCraftAndEnchantForExperienceMoveFailureForNonFullReasonContinuesBatch(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'CE Not Owned Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Not Owned Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $mockBatchSet = Mockery::mock(BatchCraftingSetService::class);
        $mockBatchSet->shouldReceive('canAccept')->andReturn(true);
        $mockBatchSet->shouldReceive('createItemInBatchCraftingSet')
            ->andReturn(['success' => false, 'reason' => 'not_owned', 'set_slot' => null]);
        $this->app->instance(BatchCraftingSetService::class, $mockBatchSet);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->failed_count);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertNotNull($result->completed_at);
    }

    public function testAlchemyForExperienceSkipsTooEasyAlchemyWorkAndUsesNextEligibleWork(): void
    {
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 300, 'game_skill_id' => $alchemy->id]);
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'alchemy_bag_limit' => 200]);
        $this->createItem(['name' => 'Too Easy Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Eligible Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $processedTooEasyItem = collect($result->action_log)->contains(fn (array $entry) => ($entry['alchemy_item']['name'] ?? null) === 'Too Easy Alchemy Item');
        $processedEligibleItem = collect($result->action_log)->contains(fn (array $entry) => ($entry['alchemy_item']['name'] ?? null) === 'Eligible Alchemy Item');

        $this->assertFalse($processedTooEasyItem);
        $this->assertTrue($processedEligibleItem);
    }

    public function testAlchemyForExperienceStopsOnlyWhenNoXpEligibleAlchemyWorkRemains(): void
    {
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 300, 'game_skill_id' => $alchemy->id]);
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'alchemy_bag_limit' => 200]);
        $this->createItem(['name' => 'Only Too Easy Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
    }

    public function testTrinketryForExperienceSkipsTooEasyTrinketAndUsesNextEligibleTrinket(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 300, false)->getCharacter();
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'copper_coins' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Too Easy Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Eligible Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedTooEasyTrinket = collect($result->action_log)->contains(fn (array $entry) => ($entry['trinketry_item']['name'] ?? null) === 'Too Easy Trinket');
        $craftedEligibleTrinket = collect($result->action_log)->contains(fn (array $entry) => ($entry['trinketry_item']['name'] ?? null) === 'Eligible Trinket');

        $this->assertFalse($craftedTooEasyTrinket);
        $this->assertTrue($craftedEligibleTrinket);
    }

    public function testTrinketryForExperienceStopsOnlyWhenNoXpEligibleTrinketryWorkRemains(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 300, false)->getCharacter();
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'copper_coins' => 1000000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Only Too Easy Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
    }

    public function testCraftSetDoesNotPermanentlySkipASlotAfterANormalCraftFailure(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 10, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Retry Set Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100000, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_set_index'] ?? null);
        $this->assertSame(0, $result->progress['craft_set_completed'] ?? null);
        $this->assertGreaterThanOrEqual(1, $result->failed_count);
    }

    public function testCraftAndEnchantSetCraftPhaseDoesNotPermanentlySkipAPlanEntryAfterANormalCraftFailure(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 10, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Retry Craft Enchant Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100000, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_craft_index'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units'] ?? null);
        $this->assertSame('crafting', $result->progress['craft_enchant_set_phase'] ?? null);
        $this->assertGreaterThanOrEqual(1, $result->failed_count);
    }

    public function testCraftAndEnchantSetEnchantPhaseDoesNotMarkAPlanEntryCompleteAfterANormalEnchantFailure(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Retry Enchant Phase Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger']);
        $prefix = $this->createItemAffix(['name' => 'Retry Enchant Phase Prefix', 'type' => 'prefix', 'cost' => 50, 'int_required' => 0, 'skill_level_required' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => [],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_set_completed_work_units'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_set_prefix_applied_count'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_set_suffix_applied_count'] ?? null);
    }

    public function testEventCraftCyclesToNextEventGoalImmediatelyWhenGoalCompletesAndANewGoalBecomesAvailable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 1, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_crafts' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'event',
                'event_mode' => true,
                'event_action' => 'craft',
                'event_type' => $event->type,
                'event_goal_id' => $goal->id,
            ],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'event_craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertSame(0, GlobalEventParticipation::where('global_event_goal_id', $goal->id)->count());

        $newGoalId = $result->progress['event_goal_id'] ?? null;
        $this->assertNotNull($newGoalId);
        $this->assertSame($event->id, \App\Flare\Models\GlobalEventGoal::find($newGoalId)?->event_id);
    }

    public function testEventCraftStopsWithEventGoalCompleteWhenNoNextGoalExists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->subMinute()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 1, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_crafts' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'event',
                'event_mode' => true,
                'event_action' => 'craft',
                'event_type' => $event->type,
                'event_goal_id' => $goal->id,
            ],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1],
            'actions' => [['action' => 'event_craft', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::EVENT_GOAL_COMPLETE->value, $result->ended_reason);
    }

    public function testUnexpectedExceptionImmediatelyCreatesAMonitoredBugReport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andThrow(new RuntimeException('Processor failed unexpectedly.'));
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(1, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testUnexpectedExceptionTellsPlayerItWasAServerIssue(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andThrow(new RuntimeException('Processor failed unexpectedly.'));
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();
        Event::fake([ServerMessageEvent::class]);

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Batch crafting stopped because of a server issue. This has been logged for investigation.';
        });
    }

    public function testNormalPerActionFailureDoesNotCreateAMonitoredBugReport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'failure' => 'Craft failed.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testCraftSetChartOutcomeCountsSuccessAndFailurePerActionNotPerCountColumn(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);
        $craftedAction = ['action' => 'craft_set', 'status' => 'crafted', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]];
        $failedAction = ['action' => 'craft_set', 'status' => 'failed', 'failure' => 'No craftable item found for type: weapon'];
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 3, 'kept_count' => 3, 'failed_count' => 3],
            'actions' => [$craftedAction, $craftedAction, $craftedAction, $failedAction, $failedAction, $failedAction],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(3, $result->progress['chart_points']['outcomes'][0]['success'] ?? null);
        $this->assertSame(3, $result->progress['chart_points']['outcomes'][0]['failure'] ?? null);
    }

    public function testCraftAmountChartOutcomeCountsSixSuccessesAndThreeFailures(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 6],
        ]);
        $craftedAction = ['action' => 'craft', 'status' => 'crafted', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]];
        $failedAction = ['action' => 'craft', 'status' => 'failed', 'failure' => 'Crafting service did not produce an inventory slot.'];
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 6, 'kept_count' => 6, 'failed_count' => 3],
            'actions' => [$craftedAction, $craftedAction, $craftedAction, $craftedAction, $craftedAction, $craftedAction, $failedAction, $failedAction, $failedAction],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(6, $result->progress['chart_points']['outcomes'][0]['success'] ?? null);
        $this->assertSame(3, $result->progress['chart_points']['outcomes'][0]['failure'] ?? null);
    }

    public function testDestroyedEnchantOutcomeCountsAsOneFailureNotOneSuccess(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 1],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['destroyed_count' => 1],
            'actions' => [['action' => 'craft_and_enchant', 'status' => 'destroyed', 'destroyed_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(0, $result->progress['chart_points']['outcomes'][0]['success'] ?? null);
        $this->assertSame(1, $result->progress['chart_points']['outcomes'][0]['failure'] ?? null);
    }

    public function testOneTickChartOutcomeDataIsPresentAfterASingleTick(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 1],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1, 'kept_count' => 1],
            'actions' => [['action' => 'craft', 'status' => 'crafted', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2]]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertCount(1, $result->progress['chart_points']['outcomes'] ?? []);
    }

    public function testCraftAmountSetsTwoSecondRetryDelayAfterNormalFailureLeavesRemainingWork(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 1, 'tick_delay_seconds' => 60],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'status' => 'failed', 'failure' => 'Crafting service did not produce an inventory slot.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(2, $result->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftAmountHidesMapTimerForTwoSecondRetryDelay(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 1, 'tick_delay_seconds' => 60],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'status' => 'failed', 'failure' => 'Crafting service did not produce an inventory slot.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $payload = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character->refresh());

        $this->assertLessThan(5, $payload['batch_crafting_time_out']);
    }

    public function testCraftSetRetriesFailedSlotWithTwoSecondDelayWhenWorkRemains(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'tick_delay_seconds' => 60],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft_set', 'status' => 'failed', 'failure' => 'No craftable item found for type: weapon']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(2, $result->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftAndEnchantAmountRetriesRemainingWorkWithTwoSecondDelay(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_enchant_specific_count' => 1, 'tick_delay_seconds' => 60],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft_and_enchant', 'status' => 'failed', 'failure' => 'Enchanting service did not apply an enchantment.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(2, $result->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftAndEnchantSetRetriesFailedEntriesWithTwoSecondDelay(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'tick_delay_seconds' => 60],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft_enchant_set_enchant', 'status' => 'failed', 'failure' => 'Enchanting service did not apply an enchantment.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(2, $result->progress['tick_delay_seconds'] ?? null);
    }

    public function testHardStopDoesNotScheduleARetryDelay(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'craft_amount' => 3, 'craft_specific_count' => 1, 'tick_delay_seconds' => 60],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE,
            'counts' => ['failed_count' => 1],
            'actions' => [['action' => 'craft', 'status' => 'failed', 'failure' => 'Inventory is full.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertFalse($result->isRunning());
        $this->assertNotSame(2, $result->progress['tick_delay_seconds'] ?? null);
    }

    public function testStatusSkillsPayloadIncludesMaxLevelForEachSkill(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 21, false)->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $weaponEntry = collect($status['batch']['skills'])->firstWhere('key', 'weapon');

        $this->assertSame(400, $weaponEntry['max_level'] ?? null);
    }

    public function testCraftAmountSkillsPayloadOnlyIncludesRelevantCraftingSkill(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Filtered Skill Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $skillKeys = collect($status['batch']['skills'])->pluck('key')->all();

        $this->assertSame(['ring'], $skillKeys);
    }

    public function testCraftAndEnchantAmountSkillsPayloadIncludesRelevantCraftSkillAndEnchanting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Filtered Enchant Amount Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $skillKeys = collect($status['batch']['skills'])->pluck('key')->all();

        $this->assertEqualsCanonicalizing(['ring', 'enchanting'], $skillKeys);
    }

    public function testCraftAndEnchantAmountSkillsPayloadIncludesDisenchantingWhenDispositionDisenchantsLosers(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Filtered Enchant Amount Disenchant Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);
        $skillKeys = collect($status['batch']['skills'])->pluck('key')->all();

        $this->assertEqualsCanonicalizing(['ring', 'enchanting', 'disenchanting'], $skillKeys);
    }

    public function testCraftAndEnchantAmountHardStopsWithIntTooLowEndReasonWhenSelectedEnchantRequiresMoreIntThanCharacterHas(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10, 'int' => 1]);
        $item = $this->createItem(['name' => 'Int Block Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $affix = $this->createItemAffix(['name' => 'High Int Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$affix->id]],
        ]);

        $service = resolve(BatchCraftingService::class);
        $afterCraftTick = $service->process($batchCrafting);
        $afterEnchantTick = $service->process($afterCraftTick);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $afterEnchantTick->ended_reason);
    }

    public function testCraftAndEnchantForExperienceDoesNotContinueRetryingWhenAutoSelectedEnchantRequiresTooMuchIntAndNoIntValidEnchantExists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10, 'int' => 1]);
        $this->createItem(['name' => 'Auto Select Int Block Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 20]);
        $this->createItemAffix(['name' => 'Only Eligible Affix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
    }

    public function testIntTooLowHardStopDoesNotCreateAMonitoredBugReport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING,
            'actions' => [['action' => 'craft_and_enchant', 'status' => 'stopped', 'failure' => 'Your Intelligence is too low for the selected enchantment.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testIntTooLowHardStopSendsPlayerFacingIntGuidanceMessage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING,
            'actions' => [['action' => 'craft_and_enchant', 'status' => 'stopped', 'failure' => 'Your Intelligence is too low for the selected enchantment.']],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();
        Event::fake([ServerMessageEvent::class]);

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Batch crafting stopped because your Intelligence is too low for the selected enchantment. Raise INT and try again.';
        });
    }

    public function testStartRejectsKeepHighestDispositionForCraftAndEnchant(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_HIGHEST->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
    }

    public function testKeepHighestForCraftSellsTheLowerLevelDuplicateItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $lowDagger = $this->createItem(['name' => 'Keep Highest Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $item = $this->createItem(['name' => 'Keep Highest High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_HIGHEST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'keep_highest_item_ids' => ['dagger' => $lowDagger->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->sold_count);
    }

    public function testKeepBestAndDisenchantRestForCraftAndEnchantDisenchantsTheLowerLevelDuplicateItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        Bus::fake([DisenchantMany::class]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $lowPrefix = $this->createItemAffix(['name' => 'Keep Best Disenchant Low Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $lowSword = $this->createItem(['name' => 'Keep Best Disenchant Low Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'skill_level_required' => 1, 'skill_level_trivial' => 1, 'item_prefix_id' => $lowPrefix->id]);
        $item = $this->createItem(['name' => 'Keep Best Disenchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Keep Best Disenchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id], 'keep_best_item_ids' => ['sword' => $lowSword->id]],
        ]);

        $service = resolve(BatchCraftingService::class);
        $afterCraftTick = $service->process($batchCrafting);
        $afterEnchantTick = $service->process($afterCraftTick);

        $disenchantedAction = collect($afterEnchantTick->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'disenchant');

        $this->assertNotNull($disenchantedAction);
        $this->assertSame('*Keep Best Disenchant Low Prefix* Keep Best Disenchant Low Sword', $disenchantedAction['disenchanted_item']['name'] ?? null);
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function testCraftExperienceKeepBestSellRestSellsReplacedRetainedSetSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 400, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $lowDagger = $this->createItem(['name' => 'Keep Best Sell Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $highDagger = $this->createItem(['name' => 'Keep Best Sell High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $lowDagger->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        $firstResult = resolve(BatchCraftingService::class)->process($batchCrafting);
        $craftedItemsSetAfterFirstTick = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $firstSlotId = $craftedItemsSetAfterFirstTick?->slots()->first()?->id;
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null, 'progress' => array_merge($firstResult->progress ?? [], ['specific_item_id' => $highDagger->id, 'craft_specific_count' => 0])]);
        $secondResult = resolve(BatchCraftingService::class)->process($firstResult->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($firstSlotId);
        $this->assertFalse($craftedItemsSet->slots()->where('id', $firstSlotId)->exists());
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame($highDagger->id, $craftedItemsSet->slots()->first()->item_id);
        $this->assertSame(1, $secondResult->sold_count);
        $this->assertSame($lowDagger->id, collect($secondResult->action_log)->pluck('sold_item.item_id')->filter()->first());
    }

    public function testCraftExperienceKeepBestDestroyRestDestroysReplacedRetainedSetSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 400, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $lowDagger = $this->createItem(['name' => 'Keep Best Destroy Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $highDagger = $this->createItem(['name' => 'Keep Best Destroy High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $lowDagger->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        $firstResult = resolve(BatchCraftingService::class)->process($batchCrafting);
        $craftedItemsSetAfterFirstTick = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $firstSlotId = $craftedItemsSetAfterFirstTick?->slots()->first()?->id;
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null, 'progress' => array_merge($firstResult->progress ?? [], ['specific_item_id' => $highDagger->id, 'craft_specific_count' => 0])]);
        $secondResult = resolve(BatchCraftingService::class)->process($firstResult->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($firstSlotId);
        $this->assertFalse($craftedItemsSet->slots()->where('id', $firstSlotId)->exists());
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame($highDagger->id, $craftedItemsSet->slots()->first()->item_id);
        $this->assertSame(1, $secondResult->destroyed_count);
        $this->assertSame($lowDagger->id, collect($secondResult->action_log)->pluck('destroyed_item.item_id')->filter()->first());
    }

    public function testCraftAndEnchantExperienceKeepBestDisenchantRestDisenchantsReplacedRetainedSetSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 400, false)
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $lowDagger = $this->createItem(['name' => 'Keep Best Disenchant Retained Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $highDagger = $this->createItem(['name' => 'Keep Best Disenchant Retained High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Keep Best Replacement Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $lowDagger->id,
                'craft_amount' => 1,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $afterCraftTick = resolve(BatchCraftingService::class)->process($batchCrafting);
        $firstResult = resolve(BatchCraftingService::class)->process($afterCraftTick->refresh());
        $craftedItemsSetAfterFirstTick = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $firstSlotId = $craftedItemsSetAfterFirstTick?->slots()->first()?->id;
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null, 'progress' => array_merge($firstResult->progress ?? [], ['specific_item_id' => $highDagger->id, 'craft_enchant_specific_count' => 0, 'craft_enchant_phase' => 'craft', 'pending_enchant_item_id' => null])]);
        $afterCraftTickTwo = resolve(BatchCraftingService::class)->process($firstResult->refresh());
        $secondResult = resolve(BatchCraftingService::class)->process($afterCraftTickTwo->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($firstSlotId);
        $this->assertFalse($craftedItemsSet->slots()->where('id', $firstSlotId)->exists());
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertGreaterThanOrEqual(1, $secondResult->progress['outcome_totals']['disenchanted'] ?? 0);
        $this->assertGreaterThan(0, collect($secondResult->action_log)->sum('gold_dust_gained'));
    }

    public function testCraftAndEnchantExperienceKeepBestSellRestSellsReplacedRetainedSetSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 400, false)
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $lowDagger = $this->createItem(['name' => 'Keep Best Enchant Sell Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $highDagger = $this->createItem(['name' => 'Keep Best Enchant Sell High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Keep Best Enchant Sell Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $lowDagger->id,
                'craft_amount' => 1,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $afterCraftTick = resolve(BatchCraftingService::class)->process($batchCrafting);
        $firstResult = resolve(BatchCraftingService::class)->process($afterCraftTick->refresh());
        $craftedItemsSetAfterFirstTick = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $firstSlotId = $craftedItemsSetAfterFirstTick?->slots()->first()?->id;
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null, 'progress' => array_merge($firstResult->progress ?? [], ['specific_item_id' => $highDagger->id, 'craft_enchant_specific_count' => 0, 'craft_enchant_phase' => 'craft', 'pending_enchant_item_id' => null])]);
        $afterCraftTickTwo = resolve(BatchCraftingService::class)->process($firstResult->refresh());
        $secondResult = resolve(BatchCraftingService::class)->process($afterCraftTickTwo->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($firstSlotId);
        $this->assertFalse($craftedItemsSet->slots()->where('id', $firstSlotId)->exists());
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertGreaterThanOrEqual(1, $secondResult->sold_count);
        $this->assertGreaterThan(0, collect($secondResult->action_log)->sum('gold_gained'));
    }

    public function testCraftAndEnchantExperienceKeepBestDestroyRestDestroysReplacedRetainedSetSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 400, false)
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $lowDagger = $this->createItem(['name' => 'Keep Best Enchant Destroy Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $highDagger = $this->createItem(['name' => 'Keep Best Enchant Destroy High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Keep Best Enchant Destroy Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $lowDagger->id,
                'craft_amount' => 1,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $afterCraftTick = resolve(BatchCraftingService::class)->process($batchCrafting);
        $firstResult = resolve(BatchCraftingService::class)->process($afterCraftTick->refresh());
        $craftedItemsSetAfterFirstTick = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $firstSlotId = $craftedItemsSetAfterFirstTick?->slots()->first()?->id;
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null, 'progress' => array_merge($firstResult->progress ?? [], ['specific_item_id' => $highDagger->id, 'craft_enchant_specific_count' => 0, 'craft_enchant_phase' => 'craft', 'pending_enchant_item_id' => null])]);
        $afterCraftTickTwo = resolve(BatchCraftingService::class)->process($firstResult->refresh());
        $secondResult = resolve(BatchCraftingService::class)->process($afterCraftTickTwo->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($firstSlotId);
        $this->assertFalse($craftedItemsSet->slots()->where('id', $firstSlotId)->exists());
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertGreaterThanOrEqual(1, $secondResult->destroyed_count);
    }

    public function testAlchemyExperienceKeepBestDestroyRestDestroysReplacedRetainedAlchemyBagItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $alchemySkill = $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value);
        $alchemySkill->update(['level' => 1, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'alchemy_bag_limit' => 20]);
        $lowItem = $this->createItem(['name' => 'Keep Best Alchemy Destroy Low Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $firstResult = resolve(BatchCraftingService::class)->process($batchCrafting);
        $firstSlot = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $lowItem->id)->first();
        $alchemySkill->refresh()->update(['level' => 2]);
        $lowItem->update(['can_craft' => false]);
        $highItem = $this->createItem(['name' => 'Keep Best Alchemy Destroy High Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null]);
        $secondResult = resolve(BatchCraftingService::class)->process($firstResult->refresh());

        $this->assertNotNull($firstSlot);
        $remainingSlots = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->get();
        $this->assertCount(1, $remainingSlots);
        $this->assertSame($highItem->id, $remainingSlots->first()->item_id);
        $this->assertGreaterThanOrEqual(1, $secondResult->destroyed_count);
    }

    public function testTrinketryExperienceKeepBestDestroyRestDestroysReplacedRetainedSetSlot(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'copper_coins' => 1000000, 'inventory_max' => 30]);
        $lowTrinket = $this->createItem(['name' => 'Keep Best Trinketry Destroy Low Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $firstResult = resolve(BatchCraftingService::class)->process($batchCrafting);
        $craftedItemsSetAfterFirstTick = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $firstSlotId = $craftedItemsSetAfterFirstTick?->slots()->first()?->id;
        $character->skills()->where('game_skill_id', $trinketry->id)->update(['level' => 2]);
        $lowTrinket->update(['can_craft' => false]);
        $highTrinket = $this->createItem(['name' => 'Keep Best Trinketry Destroy High Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 2]);
        $secondResult = resolve(BatchCraftingService::class)->process($firstResult->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertNotNull($firstSlotId);
        $this->assertFalse($craftedItemsSet->slots()->where('id', $firstSlotId)->exists());
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame($highTrinket->id, $craftedItemsSet->slots()->first()->item_id);
        $this->assertGreaterThanOrEqual(1, $secondResult->destroyed_count);
    }

    public function testAlchemyUseNowUsesValidBoonItemAsABoonOnTheCharacter(): void
    {
        Bus::fake([\App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Usable Boon Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1, 'usable' => true, 'lasts_for' => 30, 'damages_kingdoms' => false, 'can_use_on_other_items' => false]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $character->boons()->active()->where('item_id', $item->id)->count());
        $this->assertSame(0, \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->count());
        $usedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'use_now');
        $this->assertNotNull($usedAction);
    }

    public function testAlchemyUseNowKeepsKingdomBombInAlchemyBagAndDoesNotUseIt(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Kingdom Bomb Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1, 'usable' => true, 'lasts_for' => 30, 'damages_kingdoms' => true, 'can_use_on_other_items' => false]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $character->boons()->active()->where('item_id', $item->id)->count());
        $keptSlot = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($keptSlot);
        $keptAction = collect($result->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'keep');
        $this->assertNotNull($keptAction);
    }

    public function testAlchemyUseNowKeepsExtraBoonItemInAlchemyBagWhenAlreadyAtTenBoons(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);

        for ($boonIndex = 0; $boonIndex < 10; $boonIndex++) {
            $existingBoonItem = $this->createItem(['name' => 'Existing Boon Item ' . $boonIndex, 'type' => 'alchemy', 'usable' => true, 'lasts_for' => 30, 'damages_kingdoms' => false, 'can_use_on_other_items' => false]);
            $this->createCharacterBoon([
                'character_id' => $character->id,
                'item_id' => $existingBoonItem->id,
                'last_for_minutes' => 30,
                'amount_used' => 1,
                'started' => now(),
                'complete' => now()->addMinutes(30),
            ]);
        }

        $item = $this->createItem(['name' => 'Overflow Boon Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1, 'usable' => true, 'lasts_for' => 30, 'damages_kingdoms' => false, 'can_use_on_other_items' => false]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $character->boons()->active()->where('item_id', $item->id)->count());
        $keptSlot = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($keptSlot);
    }

    public function testAlchemyAmountDestroyProcessesWhenAlchemyBagIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 1]);
        $bagFillerItem = $this->createItem(['name' => 'Destroy While Full Bag Filler', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 1]);
        $item = $this->createItem(['name' => 'Alchemy Destroy While Full Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotSame(BatchCraftingEndReason::ALCHEMY_BAG_FULL->value, $result->ended_reason);
        $this->assertSame(1, $result->destroyed_count);
    }

    public function testAlchemyAmountListProcessesWhenAlchemyBagIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 1]);
        $bagFillerItem = $this->createItem(['name' => 'List While Full Bag Filler', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 1]);
        $item = $this->createItem(['name' => 'Alchemy List While Full Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotSame(BatchCraftingEndReason::ALCHEMY_BAG_FULL->value, $result->ended_reason);
        $this->assertSame(1, $result->listed_count);
    }

    public function testAlchemyAmountUseNowUsesValidBoonWhenAlchemyBagIsFull(): void
    {
        Bus::fake([\App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 1]);
        $bagFillerItem = $this->createItem(['name' => 'Use Now While Full Bag Filler', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 1]);
        $item = $this->createItem(['name' => 'Usable Boon While Full Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1, 'usable' => true, 'lasts_for' => 30, 'damages_kingdoms' => false, 'can_use_on_other_items' => false]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $character->boons()->active()->where('item_id', $item->id)->count());
        $this->assertNotSame(BatchCraftingEndReason::ALCHEMY_BAG_FULL->value, $result->ended_reason);
    }

    public function testAlchemyAmountUseNowStopsSafelyForKingdomBombWhenAlchemyBagIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 1]);
        $bagFillerItem = $this->createItem(['name' => 'Kingdom Bomb While Full Bag Filler', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 1]);
        $item = $this->createItem(['name' => 'Kingdom Bomb While Full Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1, 'usable' => true, 'lasts_for' => 30, 'damages_kingdoms' => true, 'can_use_on_other_items' => false]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::ALCHEMY_BAG_FULL->value, $result->ended_reason);
        $this->assertSame(0, \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->count());
        $this->assertSame(0, $character->boons()->active()->where('item_id', $item->id)->count());
    }

    public function testAlchemyAmountKeepStopsWhenAlchemyBagIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 1]);
        $bagFillerItem = $this->createItem(['name' => 'Keep While Full Bag Filler', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 1]);
        $item = $this->createItem(['name' => 'Keep While Full Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::ALCHEMY_BAG_FULL->value, $result->ended_reason);
        $this->assertSame(0, \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->count());
    }

    public function testAlchemyExperienceKeepBestDestroyRestSwapsWinnerWithoutRequiringExtraBagCapacity(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $alchemySkill = $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value);
        $alchemySkill->update(['level' => 1, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'alchemy_bag_limit' => 1]);
        $lowItem = $this->createItem(['name' => 'Keep Best Tight Capacity Low Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $firstResult = resolve(BatchCraftingService::class)->process($batchCrafting);
        $alchemySkill->refresh()->update(['level' => 2]);
        $lowItem->update(['can_craft' => false]);
        $highItem = $this->createItem(['name' => 'Keep Best Tight Capacity High Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 2, 'skill_level_trivial' => 400]);
        $firstResult->update(['status' => 'running', 'ended_reason' => null, 'completed_at' => null]);
        $secondResult = resolve(BatchCraftingService::class)->process($firstResult->refresh());

        $this->assertNotSame(BatchCraftingEndReason::ALCHEMY_BAG_FULL->value, $secondResult->ended_reason);
        $remainingSlots = \App\Flare\Models\AlchemyBagSlot::where('character_id', $character->id)->get();
        $this->assertCount(1, $remainingSlots);
        $this->assertSame($highItem->id, $remainingSlots->first()->item_id);
    }

    public function testBatchCraftSpecificItemEmitsManualCraftedServerMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 400, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Server Message Craft Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => $event->message === 'You crafted a: Server Message Craft Dagger!');
    }

    public function testBatchCraftAndEnchantSpecificItemEmitsManualEnchantedServerMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 400, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $item = $this->createItem(['name' => 'Server Message Enchant Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Server Message Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $afterCraftTick = resolve(BatchCraftingService::class)->process($batchCrafting);
        resolve(BatchCraftingService::class)->process($afterCraftTick->refresh());

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_starts_with($event->message, 'Applied enchantment: Server Message Enchant Prefix to:'));
    }

    public function testBatchTrinketryEmitsManualCraftedServerMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'copper_coins' => 1000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $this->createItem(['name' => 'Server Message Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => $event->message === 'You crafted a: Server Message Trinket!');
    }

    public function testBatchSellDispositionEmitsManualSoldServerMessage(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 400, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Server Message Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, fn ($event) => str_starts_with($event->message, 'Sold: Server Message Sell Dagger for:'));
    }

    public function testAlchemyAmountRejectsSellDisposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Alchemy Sell Rejected Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id],
        ]);
    }

    public function testAlchemyExperienceRejectsKeepBestSellRestDisposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);
    }

    public function testCraftAndEnchantSetDisenchantRemovesFinalOutputFromCraftedItemsSetAndRecordsGoldDust(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Disenchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $enchantedItem = $this->createItem(['name' => 'Craft Enchant Set Disenchant Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'skill_level_required' => 1, 'skill_level_trivial' => 400, 'item_prefix_id' => $prefix->id]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $enchantedItem->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_completed_final_count' => 0,
            ],
        ]);
        $goldDustBefore = $character->gold_dust;

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, $craftedItemsSet?->slots()->count() ?? 0);
        $this->assertSame(1, $result->progress['outcome_totals']['disenchanted'] ?? 0);
        $this->assertGreaterThan($goldDustBefore, $character->refresh()->gold_dust);

        $disenchantAction = collect($result->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'disenchant');
        $this->assertNotNull($disenchantAction);
        $this->assertSame($enchantedItem->id, $disenchantAction['disenchanted_item']['item_id'] ?? null);
    }

    public function testCraftAndEnchantSetListCreatesMarketBoardRowWithNonzeroListedValue(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set List Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $enchantedItem = $this->createItem(['name' => 'Craft Enchant Set List Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'skill_level_required' => 1, 'skill_level_trivial' => 400, 'item_prefix_id' => $prefix->id]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $enchantedItem->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_completed_final_count' => 0,
                'listing_price' => 5000,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $marketRow = MarketBoard::where('character_id', $character->id)->where('item_id', $enchantedItem->id)->first();
        $this->assertNotNull($marketRow);
        $this->assertSame(5000, $marketRow->listed_price);

        $listedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['disposition'] ?? null) === 'list');
        $this->assertNotNull($listedAction);
        $this->assertSame(5000, $listedAction['listed_price'] ?? null);

        $chartListedValue = collect($result->progress['chart_points']['currency'] ?? [])->sum('listed_value');
        $this->assertGreaterThan(0, $chartListedValue);
        $this->assertSame(5000, $result->progress['currency_totals']['listed_value'] ?? 0);
    }

    public function testKeepHighestForAlchemySellsTheLowerLevelDuplicateItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Keep Highest Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $trackedSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $item->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP_HIGHEST->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id, 'alchemy_keep_highest_slot' => $trackedSlot->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->sold_count);
    }

    public function testKeepHighestForTrinketrySellsTheLowerLevelDuplicateItem(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 10, false)->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'copper_coins' => 1000, 'inventory_max' => 30]);
        $lowTrinket = $this->createItem(['name' => 'Keep Highest Low Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'skill_level_required' => 1, 'skill_level_trivial' => 20]);
        $this->createItem(['name' => 'Keep Highest Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 20]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_HIGHEST->value,
            'progress' => ['keep_highest_item_ids' => ['trinket' => $lowTrinket->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThanOrEqual(1, $result->sold_count);
    }

    public function testChartPointsCurrencyRecordsGoldSpentAndGoldGainedAsSeparateNamedFields(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_HIGHEST->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1, 'sold_count' => 1],
            'actions' => [['action' => 'craft_set', 'status' => 'crafted', 'crafted_item' => ['item_id' => 1, 'slot_id' => 2], 'gold_gained' => 50]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $point = $result->progress['chart_points']['currency'][0];
        $this->assertSame(50, $point['gold_gained']);
        $this->assertArrayHasKey('gold_spent', $point);
        $this->assertArrayHasKey('gold_dust_spent', $point);
        $this->assertArrayHasKey('shards_spent', $point);
    }

    public function testTrinketryChartRecordsShardsSpentAndGoldGainedSeparately(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['shards' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['crafted_count' => 1, 'sold_count' => 1],
            'actions' => [['action' => 'trinketry', 'trinketry_item' => ['item_id' => 1, 'slot_id' => 2], 'gold_gained' => 25]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $point = $result->progress['chart_points']['currency'][0];
        $this->assertSame(25, $point['gold_gained']);
        $this->assertSame(0, $point['shards_gained']);
        $this->assertArrayHasKey('shards_spent', $point);
    }

    public function testDisenchantChartRecordsGoldSpentAndGoldDustGainedSeparately(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['disenchanted_count' => 1],
            'actions' => [['action' => 'craft_and_enchant', 'status' => 'disenchanted', 'gold_dust_gained' => 15]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $point = $result->progress['chart_points']['currency'][0];
        $this->assertSame(15, $point['gold_dust_gained']);
        $this->assertSame(0, $point['gold_gained']);
        $this->assertArrayHasKey('gold_spent', $point);
    }

    public function testCraftAndEnchantSetStatusExposesFinalItemProgressOutOfTwentyThreeInsteadOfWorkUnits(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_requested' => 23,
                'craft_enchant_set_completed_final_count' => 12,
                'craft_enchant_set_total_work_units' => 69,
                'craft_enchant_set_completed_work_units' => 34,
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame(23, $status['batch']['requested_amount']);
        $this->assertSame(12, $status['batch']['completed_amount']);
    }

    public function testPreviewReturnsStartBlockersKey(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertArrayHasKey('start_blockers', $preview);
        $this->assertIsArray($preview['start_blockers']);
    }

    public function testCraftAmountPreviewBlocksWhenTotalCostExceedsAvailableGold(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 10, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Not Enough Gold Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'not_enough_gold');

        $this->assertNotNull($blocker);
        $this->assertTrue($blocker['blocking']);
    }

    public function testCraftAmountPreviewBlocksWhenCraftedItemsSetIsFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Full Crafted Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $craftedItemsSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'crafted_items_set_full');

        $this->assertNotNull($blocker);
    }

    public function testCraftAmountPreviewBlocksWhenRequestedAmountExceedsCraftedItemsSetSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Not Enough Space Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 2]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'crafted_items_set_not_enough_space');

        $this->assertNotNull($blocker);
    }

    public function testCraftAmountStartRejectsWhenTotalCostExceedsAvailableGold(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 10, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Start Reject Gold Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ]);
    }

    public function testCraftAndEnchantAmountPreviewBlocksWhenSelectedPrefixIntTooHigh(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Prefix Int Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Preview Int Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'int_too_low_for_enchanting');

        $this->assertNotNull($blocker);
        $this->assertNotEmpty($blocker['links']);
    }

    public function testCraftAndEnchantAmountPreviewBlocksWhenSelectedSuffixIntTooHigh(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Suffix Int Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Preview Int Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$suffix->id]],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'int_too_low_for_enchanting');

        $this->assertNotNull($blocker);
    }

    public function testCraftAndEnchantAmountPreviewBlocksWhenItemPlusEnchantCostExceedsGold(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 5, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Enchant Gold Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Gold Preview Prefix', 'type' => 'prefix', 'cost' => 1000, 'int_required' => 0, 'skill_level_required' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'not_enough_gold');

        $this->assertNotNull($blocker);
    }

    public function testCraftAndEnchantAmountStartRejectsWhenSelectedAffixIntTooHigh(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Start Reject Int Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Start Reject Int Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);
    }

    public function testCraftSetPreviewBlocksWhenFullPlanCostExceedsGold(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 0, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Gold Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100000, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'not_enough_gold');

        $this->assertNotNull($blocker);
    }

    public function testCraftSetPreviewBlocksWhenCraftedItemsSetDoesNotHaveEnoughSpaceForFullQueue(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $queueCount = count(resolve(BatchCraftingProcessor::class)->craftSetQueue());
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);
        $craftedItemsSet->update(['max_slots' => max(0, $queueCount - 1)]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'crafted_items_set_not_enough_space');

        $this->assertNotNull($blocker);
    }

    public function testCraftSetStartRejectsWhenCraftedItemsSetDoesNotHaveEnoughSpaceForFullQueue(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $queueCount = count(resolve(BatchCraftingProcessor::class)->craftSetQueue());
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);
        $craftedItemsSet->update(['max_slots' => max(0, $queueCount - 1)]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);
    }

    public function testCraftAndEnchantSetPreviewBlocksWhenPlannedAffixIntTooHigh(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Preview Int Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Preview Int Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'int_too_low_for_enchanting');

        $this->assertNotNull($blocker);
    }

    public function testCraftAndEnchantSetPreviewBlocksWhenFullPlanCostExceedsGold(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 0, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Enchant Set Gold Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100000, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Gold Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Gold Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'not_enough_gold');

        $this->assertNotNull($blocker);
    }

    public function testCraftAndEnchantSetStartRejectsWhenPlannedAffixIntTooHigh(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Start Int Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Start Int Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testAlchemyAmountPreviewBlocksWhenGoldDustInsufficient(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 10, 'shards' => 1000, 'alchemy_bag_limit' => 100]);
        $item = $this->createItem(['name' => 'Gold Dust Blocker Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 100, 'shards_cost' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 5, 'alchemy_item_id' => $item->id],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'not_enough_gold_dust');

        $this->assertNotNull($blocker);
    }

    public function testAlchemyAmountPreviewBlocksWhenShardsInsufficient(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 10, 'alchemy_bag_limit' => 100]);
        $item = $this->createItem(['name' => 'Shards Blocker Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 100]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 5, 'alchemy_item_id' => $item->id],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'not_enough_shards');

        $this->assertNotNull($blocker);
    }

    public function testAlchemyAmountPreviewBlocksWhenAlchemyBagIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 5]);
        $bagFillerItem = $this->createItem(['name' => 'Bag Filler Item', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 5]);
        $item = $this->createItem(['name' => 'Bag Full Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 5, 'alchemy_item_id' => $item->id],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'alchemy_bag_full');

        $this->assertNotNull($blocker);
    }

    public function testAlchemyAmountPreviewBlocksWhenRequestedAmountExceedsBagRemainingSpace(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 2]);
        $item = $this->createItem(['name' => 'Bag Space Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 10, 'alchemy_item_id' => $item->id],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'alchemy_bag_not_enough_space');

        $this->assertNotNull($blocker);
    }

    public function testAlchemyAmountStartRejectsWhenGoldDustInsufficient(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 10, 'shards' => 1000, 'alchemy_bag_limit' => 100]);
        $item = $this->createItem(['name' => 'Start Reject Gold Dust Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 100, 'shards_cost' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 5, 'alchemy_item_id' => $item->id],
        ]);
    }

    public function testCraftEnchantSetPreviewIgnoresEquippedSelectedSetBecauseDestinationIsCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'target_set_equipped');

        $this->assertNull($blocker);
    }

    public function testCraftEnchantSetStartIgnoresEquippedSelectedSetBecauseDestinationIsCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);
        $prefix = $this->createItemAffix(['name' => 'Equipped Ignored Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);

        $this->assertArrayNotHasKey('selected_set_id', $batchCrafting->progress ?? []);
    }

    public function testCraftSetPreviewReturnsPlanEntriesForEveryQueueSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Plan Entries Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $set->id],
        ]);

        $daggerEntry = collect($preview['cost_breakdown']['plan_entries'])->firstWhere('key', 'dagger');

        $this->assertCount(23, $preview['cost_breakdown']['plan_entries']);
        $this->assertNotNull($daggerEntry);
        $this->assertSame('Plan Entries Dagger', $daggerEntry['selected_item_name'] ?? null);
    }

    public function testCraftSetPreviewCostReflectsManuallySelectedItemOverride(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 500, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Craft Set Override High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 50, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $lowDagger = $this->createItem(['name' => 'Craft Set Override Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_plan' => [
                    'dagger' => ['selected_item_id' => $lowDagger->id],
                ],
            ],
        ]);

        $this->assertSame(10, $preview['cost_breakdown']['craft_cost_total']);
    }

    public function testCraftSetStartRejectsSelectedItemNotCraftableForSlot(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItem(['name' => 'Craft Set Wrong Slot Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $sword = $this->createItem(['name' => 'Craft Set Wrong Slot Sword', 'type' => 'sword', 'crafting_type' => 'sword', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_plan' => [
                    'dagger' => ['selected_item_id' => $sword->id],
                ],
            ],
        ]);
    }

    public function testCraftAndEnchantSetPreviewAllowsEmptyNormalUnequippedSetWithNoTargetSetBlockers(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);

        $targetSetBlocker = collect($preview['start_blockers'])->first(fn (array $blocker) => str_starts_with($blocker['code'], 'target_set_'));

        $this->assertNull($targetSetBlocker);
    }

    public function testCraftAndEnchantSetPreviewAllowsValidFullTwentyThreeItemSetComposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        collect([
            'stave', 'bow', 'dagger', 'scratch-awl', 'mace', 'hammer', 'gun', 'fan', 'wand', 'censer', 'claw', 'sword',
            'shield', 'body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet',
            'ring', 'ring', 'spell-damage', 'spell-healing',
        ])->each(function (string $type) use ($set) {
            $item = $this->createItem(['type' => $type]);
            $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        });

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);

        $blockingTargetSetBlocker = collect($preview['start_blockers'])->first(fn (array $blocker) => str_starts_with($blocker['code'], 'target_set_') && $blocker['blocking']);

        $this->assertNull($blockingTargetSetBlocker);
    }

    public function testCraftAndEnchantSetPreviewIgnoresInvalidNonEmptySelectedSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'dagger'])->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'target_set_invalid_composition');

        $this->assertNull($blocker);
    }

    public function testCraftAndEnchantSetPreviewDoesNotWarnForAlreadyEnchantedSelectedSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $enchantedPrefix = $this->createItemAffix(['name' => 'Already Enchanted Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $enchantedDagger = $this->createItem(['type' => 'dagger', 'item_prefix_id' => $enchantedPrefix->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $enchantedDagger->id]);
        collect([
            'bow', 'scratch-awl', 'mace', 'hammer', 'gun', 'fan', 'wand', 'censer', 'claw', 'sword', 'stave',
            'shield', 'body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet',
            'ring', 'ring', 'spell-damage', 'spell-healing',
        ])->each(function (string $type) use ($set) {
            $item = $this->createItem(['type' => $type]);
            $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        });

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);

        $warning = collect($preview['start_blockers'])->firstWhere('code', 'target_set_already_enchanted');

        $this->assertNull($warning);
    }

    public function testCraftAndEnchantSetPreviewIgnoresMythicItemInSelectedSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $mythicDagger = $this->createItem(['type' => 'dagger', 'is_mythic' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $mythicDagger->id]);
        collect([
            'bow', 'scratch-awl', 'mace', 'hammer', 'gun', 'fan', 'wand', 'censer', 'claw', 'sword', 'stave',
            'shield', 'body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet',
            'ring', 'ring', 'spell-damage', 'spell-healing',
        ])->each(function (string $type) use ($set) {
            $item = $this->createItem(['type' => $type]);
            $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        });

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);

        $blocker = collect($preview['start_blockers'])->firstWhere('code', 'target_set_invalid_composition');

        $this->assertNull($blocker);
    }

    public function testStatusExposesActionsPerMinuteAndExperienceRateLabelForCraftExperience(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertSame(23, $status['batch']['actions_per_minute'] ?? null);
        $this->assertSame('Crafts 1 full set, 23 items, per minute.', $status['batch']['experience_rate_label'] ?? null);
    }

    public function testStatusExposesActionsPerMinuteAndExperienceRateLabelForEnchantForEvent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertSame(23, $status['batch']['actions_per_minute'] ?? null);
        $this->assertSame('Enchants up to 23 event items per minute.', $status['batch']['experience_rate_label'] ?? null);
    }

    public function testCraftAmountProcessesWhenNormalInventoryIsFullButCraftedItemsSetHasSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Full Inventory Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);
        $inventoryCountBefore = $character->getInventoryCount();

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $this->assertNotNull($craftedItemsSet);
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame($inventoryCountBefore, $character->refresh()->getInventoryCount());
        $this->assertSame(1, $result->crafted_count);
    }

    public function testCraftAndEnchantForExperienceProcessesWhenNormalInventoryIsFullButCraftedItemsSetHasSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 0]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $this->createItem(['name' => 'Full Inventory Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        $this->createItemAffix(['name' => 'Full Inventory Experience Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $inventoryCountBefore = $character->getInventoryCount();

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $this->assertNotNull($craftedItemsSet);
        $this->assertGreaterThan(0, $craftedItemsSet->slots()->count());
        $this->assertSame($inventoryCountBefore, $character->refresh()->getInventoryCount());
        $this->assertNull($result->ended_reason);
    }

    public function testCraftAndEnchantAmountProcessesWhenNormalInventoryIsFullButCraftedItemsSetHasSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Full Inventory Amount Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Full Inventory Amount Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'sword', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'enchant_affix_ids' => [$prefix->id]],
        ]);
        $inventoryCountBefore = $character->getInventoryCount();

        $service = resolve(BatchCraftingService::class);
        $afterCraftTick = $service->process($batchCrafting);
        $afterEnchantTick = $service->process($afterCraftTick);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $this->assertNotNull($craftedItemsSet);
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame($inventoryCountBefore, $character->refresh()->getInventoryCount());
    }

    public function testCraftAndEnchantSetBuildNewProcessesWhenNormalInventoryIsFullButCraftedItemsSetHasSpace(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000000, 'inventory_max' => 0]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(1000);
            })
        );
        $this->createItem(['name' => 'Full Inventory Craft Enchant Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Full Inventory Craft Enchant Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Full Inventory Craft Enchant Set Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_item_ids' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);
        $inventoryCountBefore = $character->getInventoryCount();

        resolve(BatchCraftingService::class)->process($batchCrafting);
        resolve(BatchCraftingService::class)->process($batchCrafting->refresh());
        $result = resolve(BatchCraftingService::class)->process($batchCrafting->refresh());

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $this->assertNotNull($craftedItemsSet);
        $this->assertSame(1, $craftedItemsSet->slots()->count());
        $this->assertSame($inventoryCountBefore, $character->refresh()->getInventoryCount());
        $this->assertSame(1, $result->progress['craft_enchant_set_completed_final_count'] ?? null, $result->ended_reason ?? 'no end reason');
    }

    public function testTrinketryProcessesWhenNormalInventoryIsFullButCraftedItemsSetHasSpace(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 10, false)->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'copper_coins' => 1000, 'inventory_max' => 0]);
        $this->createItem(['name' => 'Full Inventory Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);
        $inventoryCountBefore = $character->getInventoryCount();

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->count());
        $this->assertNotNull($craftedItemsSet);
        $this->assertGreaterThan(0, $craftedItemsSet->slots()->count());
        $this->assertSame($inventoryCountBefore, $character->refresh()->getInventoryCount());
        $this->assertNull($result->ended_reason);
    }

    public function testCraftAmountSellPreviewDoesNotRequireCraftedItemsSetCapacity(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Sell Full Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $craftedItemsSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ]);

        $this->assertNull(collect($preview['start_blockers'])->firstWhere('code', 'crafted_items_set_full'));
        $this->assertNull($preview['destination_capacity']);
        $this->assertSame(5, $preview['amount_preview']['effective_craftable_amount']);
    }

    public function testCraftAmountKeepPreviewStillRequiresCraftedItemsSetCapacity(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Keep Full Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $craftedItemsSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ]);

        $this->assertNotNull(collect($preview['start_blockers'])->firstWhere('code', 'crafted_items_set_full'));
        $this->assertSame('crafted_items_set', $preview['destination_capacity']['destination']);
        $this->assertSame(0, $preview['amount_preview']['effective_craftable_amount']);
    }

    public function testCraftAndEnchantAmountListPreviewIsNotCappedByCraftedItemsSetCapacity(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1000]);
        $item = $this->createItem(['name' => 'List Full Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'List Full Set Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $craftedItemsSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'enchant_affix_ids' => [$prefix->id]],
        ]);

        $this->assertNull(collect($preview['start_blockers'])->firstWhere('code', 'crafted_items_set_full'));
        $this->assertNull($preview['destination_capacity']);
        $this->assertSame(5, $preview['amount_preview']['effective_craftable_amount']);
    }

    public function testAlchemyAmountListPreviewDoesNotRequireAlchemyBagCapacity(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 1]);
        $bagFillerItem = $this->createItem(['name' => 'Alchemy List Bag Filler', 'type' => 'alchemy']);
        $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $bagFillerItem->id, 'amount' => 1]);
        $item = $this->createItem(['name' => 'Alchemy List Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 5, 'alchemy_item_id' => $item->id],
        ]);

        $this->assertNull(collect($preview['start_blockers'])->firstWhere('code', 'alchemy_bag_full'));
        $this->assertNull($preview['destination_capacity']);
        $this->assertSame(5, $preview['alchemy_amount_preview']['effective_craftable_amount']);
    }

    public function testHolyOilsSelectedListRejectsWhenAnySelectedTargetIsIneligible(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $prefix = $this->createItemAffix(['name' => 'Holy Oil Eligible Prefix', 'type' => 'prefix']);
        $eligibleItem = $this->createItem(['name' => 'Holy Oil Eligible Gear', 'type' => 'dagger', 'holy_stacks' => 1, 'item_prefix_id' => $prefix->id]);
        $ineligibleItem = $this->createItem(['name' => 'Holy Oil Ineligible Gear', 'type' => 'sword', 'holy_stacks' => 1]);
        $oil = $this->createItem(['name' => 'Holy Oil Eligibility Oil', 'type' => 'alchemy']);
        $eligibleSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $eligibleItem->id]);
        $ineligibleSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $ineligibleItem->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 2]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'selected_items' => [$eligibleSlot->id, $ineligibleSlot->id],
            'selected_oils' => [$oilSlot->id],
            'listing_price' => 1,
            'progress' => ['holy_oil_mode' => 'selected'],
        ]);
    }

    public function testHolyOilsSelectedDisenchantRejectsWhenAnySelectedTargetIsIneligible(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $prefix = $this->createItemAffix(['name' => 'Holy Oil Disenchant Prefix', 'type' => 'prefix']);
        $eligibleItem = $this->createItem(['name' => 'Holy Oil Disenchant Eligible Gear', 'type' => 'dagger', 'holy_stacks' => 1, 'item_prefix_id' => $prefix->id]);
        $ineligibleItem = $this->createItem(['name' => 'Holy Oil Disenchant Ineligible Gear', 'type' => 'sword', 'holy_stacks' => 1]);
        $oil = $this->createItem(['name' => 'Holy Oil Disenchant Oil', 'type' => 'alchemy']);
        $eligibleSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $eligibleItem->id]);
        $ineligibleSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $ineligibleItem->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 2]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'selected_items' => [$eligibleSlot->id, $ineligibleSlot->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'selected'],
        ]);
    }

    public function testMissingOutputDestinationDefaultsToCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set'],
        ]);

        $this->assertSame('crafted_items_set', $batchCrafting->progress['output_destination']);
    }

    public function testOutputSetDestinationMustBelongToCharacter(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $otherSet->id],
        ]);
    }

    public function testOutputSetDestinationMustNotBeTheCraftedItemsSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $craftedItemsSet->id],
        ]);
    }

    public function testOutputSetDestinationMustNotBeEquipped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $set->id],
        ]);
    }

    public function testOutputSetDestinationMustBeEmptyAtStart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $set->id],
        ]);
    }

    public function testOutputSetDestinationAllowsStartWithAValidEmptyUnequippedSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $set->id],
        ]);

        $this->assertSame('running', $batchCrafting->status);
        $this->assertSame($set->id, $batchCrafting->progress['output_set_id']);
    }

    public function testInventoryOutputDestinationRequiresEnoughFreeInventorySlots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 0]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory'],
        ]);
    }

    public function testPreviewReturnsInventoryDestinationCapacity(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory'],
        ]);

        $this->assertSame('inventory', $preview['destination_capacity']['destination']);
        $this->assertSame('Inventory', $preview['destination_capacity']['destination_label']);
    }

    public function testPreviewReturnsSpecifiedSetDestinationCapacity(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'My Output Set', 'max_slots' => 40]);

        $preview = resolve(BatchCraftingService::class)->preview($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $set->id],
        ]);

        $this->assertSame('inventory_set', $preview['destination_capacity']['destination']);
        $this->assertSame('My Output Set', $preview['destination_capacity']['destination_label']);
    }

    public function testStatusReturnsSelectedOutputDestinationAndSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Status Output Set']);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory_set', 'output_set_id' => $set->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('inventory_set', $status['batch']['output_destination']);
        $this->assertSame('Status Output Set', $status['batch']['output_set']['name']);
    }

    public function testNonKeepDispositionDoesNotNormalizeAnOutputDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'craft_set', 'output_destination' => 'inventory'],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
    }

    public function testContinuationStateActiveWithRetryFailedAttemptReasonAfterANormalFailure(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Continuation Retry Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $status = resolve(BatchCraftingService::class)->status($character->refresh());

        $this->assertTrue($status['batch']['continuation_state']['active']);
        $this->assertSame('waiting', $status['batch']['continuation_state']['state']);
        $this->assertSame('retry_failed_attempt', $status['batch']['continuation_state']['reason']);
        $this->assertNotNull($status['batch']['continuation_state']['next_attempt_at']);
    }

    public function testNextAttemptAtMatchesTheScheduledImmediateDelay(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Continuation Timestamp Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        $before = now();
        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $nextAttemptAt = \Carbon\Carbon::parse($result->progress['next_attempt_at']);

        $this->assertTrue($nextAttemptAt->betweenIncluded($before, $before->copy()->addSeconds(5)));
    }

    public function testMarkProcessingSetsStateToProcessingAndClearsNextAttemptAt(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'continuation_active' => true,
                'continuation_state' => 'waiting',
                'continuation_reason' => 'continue_remaining_work',
                'next_attempt_at' => now()->addSeconds(60)->toIso8601String(),
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->markProcessing($batchCrafting);

        $this->assertSame('processing', $result->progress['continuation_state']);
        $this->assertNull($result->progress['next_attempt_at']);
        $this->assertTrue($result->progress['continuation_active']);
    }

    public function testACompletedBatchClearsContinuationState(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'continuation_active' => true,
                'continuation_state' => 'waiting',
                'continuation_reason' => 'continue_remaining_work',
                'next_attempt_at' => now()->addSeconds(60)->toIso8601String(),
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD->value, $result->ended_reason);
        $this->assertFalse($result->progress['continuation_active']);
        $this->assertNull($result->progress['continuation_state']);
        $this->assertNull($result->progress['next_attempt_at']);
    }

    public function testACancelledBatchClearsContinuationState(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'continuation_active' => true,
                'continuation_state' => 'waiting',
                'continuation_reason' => 'continue_remaining_work',
                'next_attempt_at' => now()->addSeconds(60)->toIso8601String(),
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->cancel($character);

        $this->assertFalse($result->progress['continuation_active']);
        $this->assertNull($result->progress['continuation_state']);
        $this->assertNull($result->progress['next_attempt_at']);
    }

    public function testStatusExposesEveryContinuationStateKey(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'continuation_active' => true,
                'continuation_state' => 'waiting',
                'continuation_reason' => 'continue_remaining_work',
                'continuation_message' => 'Batch Crafting is continuing with the remaining requested work.',
                'continuation_phase' => 'crafting',
                'continuation_item' => 'Test Item',
                'continuation_delay_seconds' => 60,
                'next_attempt_at' => now()->addSeconds(60)->toIso8601String(),
            ],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertArrayHasKey('active', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('state', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('reason', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('message', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('phase', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('item', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('delay_seconds', $status['batch']['continuation_state']);
        $this->assertArrayHasKey('next_attempt_at', $status['batch']['continuation_state']);
    }

    public function testStatusExposesOutputDestinationLabelForInventoryDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Inventory', $status['batch']['output_destination_label'] ?? null);
    }

    public function testStatusExposesOutputDestinationLabelAsExactSetNameForInventorySetDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'My Output Set']);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $set->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('My Output Set', $status['batch']['output_destination_label'] ?? null);
    }

    public function testStatusExposesCraftedItemsSetLabelForLegacyBatchWithoutOutputDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Crafted Items Set', $status['batch']['output_destination_label'] ?? null);
    }

    public function testStatusDoesNotReplaceSelectedSetWithOutputDestinationDataForEnchantSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Enchant Set Target']);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'set', 'selected_set_id' => $set->id],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('Enchant Set Target', $status['batch']['selected_set']['name'] ?? null);
        $this->assertSame('Crafted Items Set', $status['batch']['output_destination_label'] ?? null);
    }

    public function testStartInitializesWaitingContinuationStateBeforeFirstJob(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertTrue($batchCrafting->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $batchCrafting->progress['continuation_state'] ?? null);
        $this->assertSame('continue_remaining_work', $batchCrafting->progress['continuation_reason'] ?? null);
        $this->assertSame('Batch Crafting is waiting to begin the first requested attempt.', $batchCrafting->progress['continuation_message'] ?? null);
        $this->assertSame('starting', $batchCrafting->progress['continuation_phase'] ?? null);
        $this->assertNotNull($batchCrafting->progress['next_attempt_at'] ?? null);
        $this->assertSame($batchCrafting->progress['tick_delay_seconds'] ?? null, $batchCrafting->progress['continuation_delay_seconds'] ?? null);
    }

    public function testStatusExposesInitialWaitingContinuationImmediatelyAfterStart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertSame('waiting', $status['batch']['continuation_state']['state'] ?? null);
        $this->assertNotNull($status['batch']['continuation_state']['next_attempt_at'] ?? null);
    }

    public function testMarkProcessingChangesInitialWaitingContinuationToProcessingAndClearsNextAttemptAt(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $processed = resolve(BatchCraftingService::class)->markProcessing($batchCrafting);

        $this->assertSame('processing', $processed->progress['continuation_state'] ?? null);
        $this->assertArrayHasKey('next_attempt_at', $processed->progress);
        $this->assertNull($processed->progress['next_attempt_at']);
    }

    public function testStatusDoesNotExposeKeptOutputCommittedFlag(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Kept Output Flag Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);
        resolve(BatchCraftingService::class)->process($batchCrafting);

        $status = resolve(BatchCraftingService::class)->status($character);

        $this->assertArrayNotHasKey('kept_output_committed', $status['batch'] ?? []);
        foreach (($status['batch']['action_history'] ?? $status['batch']['action_log'] ?? []) as $entry) {
            $this->assertArrayNotHasKey('kept_output_committed', $entry);
        }
    }

    public function testCraftSetAdvancesOnlyAfterOutputSlotExists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'Service Craft Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->instance(
            BatchCraftingSetService::class,
            Mockery::mock(BatchCraftingSetService::class, function ($mock) {
                $mock->shouldReceive('canAccept')->andReturn(true);
                $mock->shouldReceive('createItemInBatchCraftingSet')->andReturn(['success' => false, 'reason' => 'unexpected_error', 'set_slot' => null]);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'output_destination' => 'crafted_items_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_keys' => ['dagger'],
                'craft_set_index' => 0,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_set_index'] ?? null);
        $this->assertSame(0, $result->progress['craft_set_completed'] ?? null);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
    }

    public function testFailedDestinationCommitDoesNotIncrementModelKeptCountColumn(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Service Regression Kept Count Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->instance(
            BatchCraftingSetService::class,
            Mockery::mock(BatchCraftingSetService::class, function ($mock) {
                $mock->shouldReceive('canAccept')->andReturn(true);
                $mock->shouldReceive('createItemInBatchCraftingSet')->andReturn(['success' => false, 'reason' => 'unexpected_error', 'set_slot' => null]);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->fresh()->kept_count);
    }

    public function testFailedDestinationCommitDoesNotPersistAKeptDispositionInTheActionLog(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Service Regression Action Log Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->instance(
            BatchCraftingSetService::class,
            Mockery::mock(BatchCraftingSetService::class, function ($mock) {
                $mock->shouldReceive('canAccept')->andReturn(true);
                $mock->shouldReceive('createItemInBatchCraftingSet')->andReturn(['success' => false, 'reason' => 'unexpected_error', 'set_slot' => null]);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        foreach ($result->fresh()->action_log as $entry) {
            $this->assertNotSame('keep', $entry['disposition'] ?? null);
            $this->assertArrayNotHasKey('kept_item', $entry);
        }
    }

    public function testSuccessfulCraftExperienceCycleResetsAndStoresWaitingContinuationForNextScheduledAttempt(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $this->createItem(['name' => 'Cycle Reset Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'experience_cycle_actions' => 18],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['experience_cycle_actions'] ?? null);
        $this->assertTrue($result->isRunning());
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(60, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertNotNull($result->progress['next_attempt_at'] ?? null);
        $this->assertSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testSuccessfulCraftAndEnchantExperienceCycleResetsAndStoresWaitingContinuationForNextScheduledAttempt(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false, ['xp' => 0, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $this->createItem(['name' => 'CE Cycle Reset Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'CE Cycle Reset Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $enchantedItem = $item->replicate();
                    $enchantedItem->name = $item->name.' Enchanted '.(((int) \App\Flare\Models\Item::max('id')) + 1);
                    $enchantedItem->item_prefix_id = $prefix->id;
                    $enchantedItem->save();

                    return ['success' => true, 'item' => $enchantedItem, 'reason' => null];
                });
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_affix_ids' => [$prefix->id], 'experience_cycle_actions' => 18],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['experience_cycle_actions'] ?? null);
        $this->assertTrue($result->isRunning());
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(60, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testSuccessfulAlchemyExperienceTickStoresWaitingContinuationForNextScheduledAttempt(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 1, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 50]);
        $this->createItem(['name' => 'Alchemy Continuation Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(60, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertNotNull($result->progress['next_attempt_at'] ?? null);
        $this->assertSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testSuccessfulTrinketryExperienceTickStoresWaitingContinuationForNextScheduledAttempt(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 300, false)->getCharacter();
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'copper_coins' => 1000000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $this->createItem(['name' => 'Trinketry Continuation Item', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(60, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testSuccessfulEventCraftTickLeavesEventRunningAndStoresWaitingContinuation(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 1, false, ['xp' => 0, 'xp_max' => 100, 'skill_bonus' => 1.0])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $this->createItem(['name' => 'Event Craft Continuation Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(60, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testSuccessfulEventEnchantTickLeavesEventRunningAndStoresWaitingContinuation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 1, 'xp' => 0, 'xp_max' => 100, 'skill_bonus' => 1.0]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Event Enchant Continuation Target', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Event Enchant Continuation Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(60, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testFiniteRemainingWorkStillUsesTwoSecondDelayAndContinuingMessage(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Finite Remaining Continuation Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 10,
                'craft_specific_count' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(2, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertSame('Batch Crafting is continuing with the remaining requested work.', $result->progress['continuation_message'] ?? null);
    }

    public function testFailedAttemptContinuationMessageIsRetryMessageNotGenericMessage(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Retry Message Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'craft_specific_count' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame('retry_failed_attempt', $result->progress['continuation_reason'] ?? null);
        $this->assertSame('That attempt failed, but Batch Crafting is still running and will try again.', $result->progress['continuation_message'] ?? null);
        $this->assertNotSame('Batch Crafting is waiting before the next scheduled attempt.', $result->progress['continuation_message'] ?? null);
    }

    public function testDestroyedCraftAndEnchantItemContinuationTakesPriorityOverGenericMessage(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false, ['xp' => 0, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $this->createItem(['name' => 'Destroyed Priority Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Destroyed Priority Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturn(['success' => false, 'item' => null, 'reason' => 'destroyed']);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame('recraft_destroyed_item', $result->progress['continuation_reason'] ?? null);
        $this->assertSame('The item shattered during enchanting. A replacement will be crafted and enchanted again.', $result->progress['continuation_message'] ?? null);
    }

    public function testCraftAmountBoundedChunkKeepsRunningWithTwoSecondWaitingContinuation(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Continuation Bounded Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 10,
                'craft_specific_count' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertSame(6, $result->progress['craft_specific_count'] ?? null);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(2, $result->progress['continuation_delay_seconds'] ?? null);
        $this->assertNotNull($result->progress['next_attempt_at'] ?? null);
    }

    public function testCraftAndEnchantAmountBoundedChunkKeepsRunningWithTwoSecondWaitingContinuation(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Continuation Bounded CE Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Continuation Bounded CE Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 10,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertSame(3, $result->progress['craft_enchant_specific_count'] ?? null);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(2, $result->progress['continuation_delay_seconds'] ?? null);
    }

    public function testAlchemyAmountBoundedChunkKeepsRunningWithTwoSecondWaitingContinuation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold_dust' => 100000, 'shards' => 100000, 'alchemy_bag_limit' => 50, 'inventory_max' => 10]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Continuation Bounded Alchemy Amount Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 10, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertSame(6, $result->progress['alchemy_amount_count'] ?? null);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(2, $result->progress['continuation_delay_seconds'] ?? null);
    }

    public function testHolyOilsSelectedBoundedChunkKeepsRunningWithTwoSecondWaitingContinuation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 100000, 'inventory_max' => 20]);
        $item = $this->createItem(['name' => 'Continuation Bounded Holy Oil Item', 'type' => 'weapon', 'holy_stacks' => 1, 'cost' => 1]);
        $slotOne = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotTwo = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotThree = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotFour = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotFive = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotSix = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotSeven = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Continuation Bounded Holy Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 20]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$slotOne->id, $slotTwo->id, $slotThree->id, $slotFour->id, $slotFive->id, $slotSix->id, $slotSeven->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertSame(6, $result->progress['holy_oil_completed_applications'] ?? null);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(2, $result->progress['continuation_delay_seconds'] ?? null);
    }

    public function testStartStripsOutputDestinationForCraftExperienceProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'output_destination' => 'inventory', 'output_set_id' => 999],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
        $this->assertArrayNotHasKey('output_set_id', $batchCrafting->progress ?? []);
    }

    public function testStartStripsOutputDestinationForEventCraftProgress(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event', 'output_destination' => 'inventory'],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
    }

    public function testStartStripsOutputDestinationForEventEnchantProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event', 'output_destination' => 'inventory'],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
    }

    public function testStartStripsOutputDestinationForAlchemyAmountProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000]);
        $item = $this->createItem(['name' => 'Strip Alchemy Amount Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id, 'output_destination' => 'inventory'],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
    }

    public function testStartStripsOutputDestinationForTrinketryProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['shards' => 1000]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience', 'output_destination' => 'crafted_items_set'],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
    }

    public function testStartStripsOutputDestinationForHolyOilsSelectedProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'selected', 'output_destination' => 'inventory'],
        ]);

        $this->assertArrayNotHasKey('output_destination', $batchCrafting->progress ?? []);
    }

    public function testDestroyedEventEnchantUsesImmediateWaitingContinuation(): void
    {
        $now = Carbon::parse('2026-07-18 12:00:00');
        Carbon::setTestNow($now);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);
        $processor = Mockery::mock(BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['destroyed_count' => 1],
            'actions' => [[
                'action' => 'event_enchant',
                'status' => 'destroyed',
                'destroyed_item' => ['item_id' => 1, 'name' => 'Shattered Event Item'],
                'failure' => 'The event item shattered while enchanting and was destroyed.',
            ]],
        ]);
        $logger = Mockery::mock(BatchCraftingLogger::class)->shouldIgnoreMissing();

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger, resolve(EnchantingService::class), resolve(BatchCraftingSetService::class), resolve(HolyItemService::class)))->process($batchCrafting);

        $this->assertTrue($result->isRunning());
        $this->assertSame(1, $result->destroyed_count);
        $this->assertSame(2, $result->progress['tick_delay_seconds'] ?? null);
        $this->assertTrue($result->progress['continuation_active'] ?? false);
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame('retry_failed_attempt', $result->progress['continuation_reason'] ?? null);
        $this->assertSame('The event item shattered while enchanting. Batch Crafting will continue with the next event item or craft a replacement.', $result->progress['continuation_message'] ?? null);
        $this->assertSame($now->copy()->addSeconds(2)->toIso8601String(), $result->progress['next_attempt_at'] ?? null);
        Carbon::setTestNow();
    }
}
