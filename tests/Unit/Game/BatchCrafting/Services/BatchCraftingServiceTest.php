<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Flare\Models\InventorySet;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Services\BatchCraftingLogger;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateUser;

class BatchCraftingServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacter, CreateGameSkill, CreateInventorySlot, CreateItem, CreateItemAffix, CreateUser, MockeryPHPUnitIntegration, RefreshDatabase;

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

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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
        $logger = Mockery::mock(BatchCraftingLogger::class);
        $logger->shouldReceive('batchStarted')->once();

        (new BatchCraftingService(
            Mockery::mock(BatchCraftingProcessor::class),
            resolve(CraftingService::class),
            $logger,
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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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

        $result = (new BatchCraftingService($processor, resolve(CraftingService::class), $logger))->process($batchCrafting);

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
}
