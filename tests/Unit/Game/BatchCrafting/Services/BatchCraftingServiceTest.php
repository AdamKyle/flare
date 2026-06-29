<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Services\BatchCraftingLogger;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->createItem(['name' => 'Max Level Batch Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNull($result->ended_reason);
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
        $this->createItem(['name' => 'Max Level Batch Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItemAffix(['name' => 'Max Level Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $service = resolve(BatchCraftingService::class);
        $result = $service->process($batchCrafting);
        $result = $service->process($result);

        $this->assertNull($result->ended_reason);
        $this->assertGreaterThan(0, $result->crafted_count);
        $this->assertNotNull($result->action_log[1]['enchanted_item'] ?? null);
    }

    public function testCraftBatchProcessesOneItemPerTickAndPersistsQueueInProgress(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'State Machine Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->crafted_count);
        $this->assertNotNull($result->progress['craft_queue'] ?? null);
        $this->assertSame(1, $result->progress['craft_queue_index'] ?? null);
    }

    public function testCraftAndEnchantFirstTickCraftsAndSetsEnchantPhaseInProgress(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'Phase Craft Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame('enchant', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertNotNull($result->progress['pending_enchant_slot_id'] ?? null);
        $this->assertSame(1, $result->crafted_count);
        $this->assertSame('crafted', $result->action_log[0]['status'] ?? null);
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
        $this->createItem(['name' => 'Phase Enchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItemAffix(['name' => 'Phase Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $service = resolve(BatchCraftingService::class);
        $afterCraftTick = $service->process($batchCrafting);
        $afterEnchantTick = $service->process($afterCraftTick);

        $this->assertSame('craft', $afterEnchantTick->progress['craft_enchant_phase'] ?? null);
        $this->assertArrayHasKey('pending_enchant_slot_id', $afterEnchantTick->progress ?? []);
        $this->assertNull($afterEnchantTick->progress['pending_enchant_slot_id']);
        $this->assertNotNull($afterEnchantTick->action_log[1]['enchanted_item'] ?? null);
        $this->assertSame(1, $afterEnchantTick->progress['craft_enchant_index'] ?? null);
    }

    public function testFullSetQueueIncludesTwoRingEntries(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->assignSkill($ringCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Queue Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $queue = $result->progress['craft_queue'] ?? [];
        $ringEntries = array_filter($queue, fn (array $entry) => ($entry['type'] ?? '') === 'ring');
        $this->assertCount(2, $ringEntries);
    }

    public function testMaxLevelEnchantBatchStillEnchantsEligibleOwnedItemWhenEligibleAffixExists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Max Level Enchant Target', 'type' => 'weapon', 'crafting_type' => 'weapon', 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Max Level Enchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ENCHANT->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
        $this->assertNotNull($result->action_log[0]['enchanted_item'] ?? null);
    }

    public function testMaxLevelAlchemyBatchStillTransmutesWhenEligibleAlchemyItemExists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value;
        })->update(['level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 10]);
        $this->createItem(['name' => 'Max Level Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ALCHEMY->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertSame(1, $result->crafted_count);
        $this->assertSame(1, $result->kept_count);
    }

    public function testMaxLevelTrinketryBatchStillCraftsWhenEligibleTrinketItemExists(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketry, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'copper_coins' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'Max Level Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::TRINKETRY->value, 'disposition' => BatchCraftingDisposition::KEEP->value]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertSame(1, $result->crafted_count);
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
        $this->createItem(['name' => 'Batch Disenchant Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItemAffix(['name' => 'Batch Disenchant Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value, 'disposition' => BatchCraftingDisposition::DISENCHANT->value]);

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
        $this->assertSame(1, $result->failed_count);
    }

    public function testCraftExperienceModeStopsWhenSkillIsMaxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'specific_crafting_type' => 'weapon'],
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

    public function testTrinketryAmountModeStopsWhenAmountReached(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 1000, 'copper_coins' => 1000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_amount' => 3, 'trinketry_amount_count' => 3],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
    }

    public function testFullSetInventoryPreflightStopsIfInsufficientSpace(): void
    {
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignSkill($ringCrafting, 1, false)
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 2]);
        $fillerItem = $this->createItem(['name' => 'Preflight Filler', 'type' => 'sword', 'cost' => 1]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $fillerItem->id]);
        $this->createItem(['name' => 'Preflight Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
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
            'progress' => ['craft_mode' => 'full_set', 'set_count' => 1],
        ]);

        $this->assertSame(1, BatchCrafting::where('character_id', $character->id)->count());
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
}
