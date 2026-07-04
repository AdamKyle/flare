<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Flare\Models\HolyStack;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Services\BatchCraftingLogger;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Flare\Values\ItemSpecialtyType;
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
use Tests\Traits\CreateUser;

class BatchCraftingServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacter, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalCraftingInventory, CreateGlobalCraftingInventorySlot, CreateGlobalEventGoal, CreateInventorySets, CreateInventorySlot, CreateItem, CreateItemAffix, CreateUser, MockeryPHPUnitIntegration, RefreshDatabase;

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

    public function testStopOnNoInventorySpace(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 0, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
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
        $this->assertArrayHasKey('pending_enchant_slot_id', $afterEnchantTick->progress ?? []);
        $this->assertNull($afterEnchantTick->progress['pending_enchant_slot_id']);
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
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $targetItem->id]);
        $oilItem = $this->createItem(['name' => 'Level 2 Holy Oil', 'type' => 'alchemy', 'holy_level' => 2, 'can_use_on_other_items' => true]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oilItem->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$targetItem->id],
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
        $craftingService = Mockery::mock(CraftingService::class);
        $craftingService->shouldReceive('fetchCraftableItems')->andReturnUsing(function ($character, array $params, bool $includeDetails = false) {
            if ($params['crafting_type'] === 'armour') {
                return new EloquentCollection(array_map(fn (string $type, int $index) => (object) ['id' => 2000 + $index, 'type' => $type, 'skill_level_required' => 1], ArmourType::allTypes(), array_keys(ArmourType::allTypes())));
            }

            if ($params['crafting_type'] === 'ring') {
                return new EloquentCollection([(object) ['id' => 3000, 'type' => ItemType::RING->value, 'skill_level_required' => 1]]);
            }

            if ($params['crafting_type'] === 'spell') {
                return new EloquentCollection([
                    (object) ['id' => 4000, 'type' => ItemType::SPELL_DAMAGE->value, 'skill_level_required' => 1],
                    (object) ['id' => 4001, 'type' => ItemType::SPELL_HEALING->value, 'skill_level_required' => 1],
                ]);
            }

            return new EloquentCollection([(object) ['id' => 1000, 'type' => $params['crafting_type'], 'skill_level_required' => 1]]);
        });
        $craftingService->shouldReceive('craft')->andReturnFalse();
        $craftingService->shouldReceive('getLastCraftedInventorySlotId')->andReturnNull();
        $processor = new BatchCraftingProcessor(
            $craftingService,
            resolve(AlchemyService::class),
            resolve(TrinketCraftingService::class),
            resolve(EnchantingService::class),
            resolve(HolyItemService::class),
            resolve(MultiInventoryActionService::class),
            resolve(BatchCraftingSetService::class),
            resolve(InventorySetService::class),
        );

        $result = $processor->processOneTick($batchCrafting, $character->refresh());

        $this->assertCount(23, $batchCrafting->refresh()->progress['craft_experience_queue'], $result['end_reason']?->value ?? 'no end reason');
        $this->assertSame(138, array_sum($result['counts']));
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
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Keep Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
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
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'CE Keep Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
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
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Enchant Fail Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->refresh()->failed_count);
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

    public function testBatchDispositionSellGroupsMultipleSlotsIntoBulkSellOperation(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 20]);
        $item = $this->createItem(['name' => 'Bulk Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $mockMultiInv = Mockery::mock(MultiInventoryActionService::class);
        $mockMultiInv->shouldReceive('sellManyItems')->once()->andReturn(['status' => 200]);
        $this->app->instance(MultiInventoryActionService::class, $mockMultiInv);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 2],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);
    }

    public function testBatchCraftingSetMoveFailureForNonFullReasonEndsWithExplicitFailedStatus(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Not Owned Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $mockBatchSet = Mockery::mock(BatchCraftingSetService::class);
        $mockBatchSet->shouldReceive('canAccept')->andReturn(true);
        $mockBatchSet->shouldReceive('moveInventorySlotIntoBatchCraftingSet')
            ->andReturn(['success' => false, 'reason' => 'not_owned', 'set_slot' => null]);
        $this->app->instance(BatchCraftingSetService::class, $mockBatchSet);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
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
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
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
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftSetValidatesSelectedSetOwnership(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $otherSet->id],
        ]);
    }

    public function testCraftSetStartsWithOneMinutePendingTimer(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $set->id],
        ]);

        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds'] ?? null);
    }

    public function testCraftSetContinuesToNextSetItemAfterAnIndividualFailedAttemptWhenContinuingIsPossible(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
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

    public function testCraftSetPutsCraftedItemsIntoSelectedSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
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

        $this->assertSame(1, $set->refresh()->slots()->count());
        $this->assertSame(1, $result->progress['craft_set_completed'] ?? null, $result->ended_reason ?? 'no end reason');
    }

    public function testCraftSetStopsWhenSelectedSetIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 1]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);
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

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL->value, $result->ended_reason);
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

    public function testStatusExposesChartPointsForEntireRun(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Chart Point Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
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

        $slot = $set->refresh()->slots()->first();
        $this->assertSame($highItem->id, $slot->item_id);
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
        $character->update(['gold_dust' => 1000, 'inventory_max' => 10]);
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
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => [['type' => 'helmet', 'crafting_type' => 'armour']],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame($highItem->id, $set->refresh()->slots()->first()->item_id);
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
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
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

        $this->assertSame(3, $status['batch']['chart_points']['outcomes'][0]['success'] ?? null);
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

        $this->assertSame(3, $status['batch']['chart_points']['outcomes'][0]['success'] ?? null);
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
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
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
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Event XP Enchant Target', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_id' => $goal->id, 'character_id' => $character->id]);
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
            ->assignSkill($weaponCrafting, 4, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 400, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Enchant Maxed Still Crafts Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
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

    public function testCraftAndEnchantSetRejectsSetNotOwnedByCharacter(): void
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

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $otherSet->id, 'enchant_plan' => $plan],
        ]);
    }

    public function testCraftAndEnchantSetRejectsMissingPlannedItemPrefix(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $suffix = $this->createItemAffix(['name' => 'Missing Prefix Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => null, 'suffix_affix_id' => $suffix->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => $plan],
        ]);
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

    public function testCraftAndEnchantSetOnlySupportsKeepDisposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Craft Enchant Set Disposition Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Craft Enchant Set Disposition Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
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

    public function testCraftAndEnchantSetPlacesCompletedFinalItemsIntoSelectedDestinationSet(): void
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
        $result = resolve(BatchCraftingService::class)->process($batchCrafting->refresh());

        $this->assertSame(1, $set->refresh()->slots()->count());
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
        $item = $this->createItem(['name' => 'Moved To Set Log Item', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Moved To Set Log Destination']);
        $inventorySlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
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
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => ['dagger' => $inventorySlot->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $finalizeEntry = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_finalize');

        $this->assertSame('Moved To Set Log Destination', $finalizeEntry['destination_set'] ?? null);
        $this->assertTrue($finalizeEntry['moved_to_set'] ?? false);
        $this->assertSame(1, $set->refresh()->slots()->count());
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

    public function testCraftSetRejectsCraftedItemsSetAsDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $craftedItemsSet->id],
        ]);
    }

    public function testCraftEnchantSetRejectsCraftedItemsSetAsDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $craftedItemsSet->id, 'enchant_plan' => []],
        ]);
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

    public function testCraftSetRejectsNonEmptySelectedSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'selected_set_id' => $set->id],
        ]);
    }

    public function testCraftEnchantSetRejectsNonEmptySelectedSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);

        $this->expectException(ValidationException::class);

        resolve(BatchCraftingService::class)->start($character, [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id, 'enchant_plan' => []],
        ]);
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
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Destroyed Enchant Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $prefix = $this->createItemAffix(['name' => 'Destroyed Enchant Set Prefix', 'type' => 'prefix', 'cost' => 25, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Destroyed Enchant Set Suffix', 'type' => 'suffix', 'cost' => 25, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $inventorySlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
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
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => ['dagger' => $inventorySlot->id],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 1,
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
        $this->assertSame(3, $result->fresh()->progress['craft_enchant_set_completed_work_units'] ?? null);
        $this->assertNull(InventorySlot::find($inventorySlot->id));
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
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
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
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Holy Preview Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
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

    public function testCraftEnchantSetFinalizePhaseDoesNotMoveDestroyedItemIntoDestinationSet(): void
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
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'finalizing',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 1,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => ['dagger' => 999999],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 2,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertNull($result->fresh()->progress['craft_enchant_set_completed_final_count'] ?? null);
    }
}
