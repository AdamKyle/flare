<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers\Api;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\InventorySet;
use App\Game\Automation\Values\AutomationType;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBag;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateInventory;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateScheduledEvent;
use Tests\Traits\CreateSkill;
use Tests\Traits\CreateUser;

class BatchCraftingControllerTest extends TestCase
{
    use CreateAlchemyBag, CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacter, CreateCharacterAutomation, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateInventory, CreateInventorySets, CreateInventorySlot, CreateItem, CreateItemAffix, CreateScheduledEvent, CreateSkill, CreateUser, RefreshDatabase;

    public function test_start_craft_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first());
    }

    public function test_start_craft_and_enchant_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first());
    }

    public function test_cannot_start_enchant_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ENCHANT->value)->first());
    }

    public function test_start_alchemy_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function test_start_alchemy_batch_rejects_locked_alchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => true]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function test_start_holy_oils_batch_rejects_locked_alchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => true]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::HOLY_OILS->value)->first());
    }

    public function test_start_alchemy_batch_rejects_sell_disposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function test_start_holy_oils_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::HOLY_OILS->value)->first());
    }

    public function test_start_trinketry_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100, 'copper_coins' => 100, 'shards' => 100]);
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $trinketry->id, 'character_id' => $character->id, 'level' => 1, 'xp' => 0, 'xp_max' => 100, 'is_locked' => false]);
        $this->createItem(['type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::TRINKETRY->value)->first());
    }

    public function test_reject_invalid_disposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => 'invalid',
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_list_for_craft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_list_for_holy_oils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_list_for_trinketry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_allow_list_for_craft_and_enchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'listing_price' => 50,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function test_reject_list_for_enchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_allow_list_for_alchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'listing_price' => 1,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function test_allow_disenchant_for_craft_and_enchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::DISENCHANT->value)->first());
    }

    public function test_reject_disenchant_for_craft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_disenchant_for_enchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_keep_best_disenchant_rest_for_enchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_craft_experience_does_not_require_specific_crafting_type(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
        $this->assertArrayNotHasKey('set_count', $batchCrafting->progress ?? []);
    }

    public function test_reject_full_set_craft_mode(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'full_set'],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_craft_and_enchant_specific_item_starts_with_amount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Controller Batch Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Controller Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'sword',
                'specific_item_id' => $item->id,
                'craft_amount' => 2,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();
        $this->assertSame(2, $batchCrafting->progress['craft_amount'] ?? null);
    }

    public function test_craft_and_enchant_specific_item_accepts_one_prefix_affix(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Controller Prefix Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Single Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'sword',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_craft_and_enchant_specific_item_accepts_one_suffix_affix(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Controller Suffix Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Single Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'sword',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'enchant_affix_ids' => [$suffix->id],
            ],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_craft_and_enchant_specific_item_accepts_prefix_and_suffix_affixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Controller Both Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Both Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Both Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'sword',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
            ],
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();
        $this->assertCount(2, $batchCrafting->progress['enchant_affix_ids'] ?? []);
    }

    public function test_alchemy_amount_requires_selected_item(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_disenchant_for_alchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_disenchant_for_holy_oils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_disenchant_for_trinketry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_keep_best_destroy_rest_allowed_for_craft_experience(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value)->first());
    }

    public function test_keep_best_destroy_rest_rejected_for_craft_amount(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $item = $this->createItem(['name' => 'Keep Best Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_trinketry_allows_keep_best_destroy_rest_disposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100, 'copper_coins' => 100, 'shards' => 100]);
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $trinketry->id, 'character_id' => $character->id, 'level' => 1, 'xp' => 0, 'xp_max' => 100, 'is_locked' => false]);
        $this->createItem(['type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value)->first());
    }

    public function test_alchemy_amount_allows_use_now_disposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::USE_NOW->value)->first());
    }

    public function test_start_rejects_list_disposition_without_listing_price(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_holy_oils_list_rejected_when_selected_item_has_no_enchants(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'listing_price' => 50,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_holy_oils_list_allowed_when_selected_item_has_enchants(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'Holy Oil List Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1, 'item_prefix_id' => $prefix->id]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'listing_price' => 50,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function test_cancel_active_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('cancelled_at')->first());
    }

    public function test_cannot_start_duplicate_active_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertSame(1, BatchCrafting::where('character_id', $character->id)->count());
    }

    public function test_cannot_start_while_faction_loyalty_is_running(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createCharacterAutomation(['character_id' => $character->id, 'type' => AutomationType::FACTION_LOYALTY->value]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_can_coexist_with_exploration_where_existing_rules_allow_it(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $this->createCharacterAutomation(['character_id' => $character->id, 'type' => AutomationType::EXPLORING->value]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_holy_oils_without_selected_items(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_holy_oils_without_selected_oils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [1],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_holy_oils_item_not_owned_by_character(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_holy_oils_oil_not_owned_by_character(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $otherUser = $this->createUser();
        $otherCharacter = $this->createCharacter(['user_id' => $otherUser->id, 'name' => 'other-batch-crafter', 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $otherAlchemyBag = $this->createAlchemyBag(['character_id' => $otherCharacter->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $otherAlchemyBag->id, 'character_id' => $otherCharacter->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_holy_oils_ineligible_item(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'trinket', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_holy_oils_invalid_oil_stack(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => false, 'holy_level' => null]);
        $itemSlot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$itemSlot->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_craft_experience_when_all_crafting_skills_maxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_allow_craft_experience_start_when_submitted_skill_is_maxed_but_another_skill_is_not(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 2, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'craft_experience_skill' => 'weapon'],
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();

        $this->assertNotNull($batchCrafting);
        $this->assertArrayNotHasKey('craft_experience_skill', $batchCrafting->progress ?? []);
    }

    public function test_craft_and_enchant_experience_starts_when_only_enchanting_is_maxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->update(['level' => 5]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_craft_and_enchant_experience_when_all_crafting_skills_and_enchanting_are_maxed(): void
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
        $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->update(['level' => 5]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_alchemy_experience_when_alchemy_is_maxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 100, 'inventory_max' => 10]);
        $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', 'Alchemy'))
            ->update(['level' => 5]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_reject_trinketry_when_trinketry_is_maxed(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($trinketry, 5, false)
            ->getCharacter();
        $character->update(['shards' => 100, 'inventory_max' => 10]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_do_not_reject_event_crafting_when_crafting_skills_maxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 5, false)
            ->assignSkill($ringCrafting, 5, false)
            ->assignSkill($spellCrafting, 5, false)
            ->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $character = $character->refresh();

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event'],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_do_not_reject_event_enchanting_when_enchanting_is_maxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->update(['level' => 5]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $character = $character->refresh();

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_cancel_is_idempotent_when_no_batch_running(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_preview_returns_craft_amount_data_before_batch_is_started(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $item = $this->createItem(['name' => 'Preview Endpoint Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'cost' => 25]);

        $response = $this->actingAs($user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 4],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertSame('crafted_items_set', $response->json('amount_preview.destination'));
        $this->assertSame(4, $response->json('amount_preview.effective_craftable_amount'));
    }

    public function test_preview_craft_and_enchant_set_returns_non_zero_enchant_cost_through_http_validation(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $this->createInventory(['character_id' => $character->id]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $this->createItem(['name' => 'Preview Set Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'HTTP Preview Prefix', 'type' => 'prefix', 'cost' => 30, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'HTTP Preview Suffix', 'type' => 'suffix', 'cost' => 40, 'int_required' => 0, 'skill_level_required' => 1]);

        $response = $this->actingAs($user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => [
                    'left_hand' => ['selected_item_id' => $this->createItem(['name' => 'Selected Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1])->id, 'prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
            ],
        ]);

        $this->assertSame(70, $response->json('cost_breakdown.enchant_cost_total'));
    }

    public function test_preview_craft_enchant_set_returns_all_twelve_set_targets(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);

        $this->assertCount(12, $response->json('cost_breakdown.plan_entries'));
    }

    public function test_preview_craft_enchant_set_defaults_to_highest_craftable_item_per_target(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 10]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $highItem = $this->createItem(['name' => 'High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 50, 'skill_level_required' => 3, 'skill_level_trivial' => 1]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);

        $daggerEntry = collect($response->json('cost_breakdown.plan_entries'))->firstWhere('key', 'left_hand');

        $this->assertNull($daggerEntry['selected_item_id']);
        $this->assertSame($highItem->id, collect($daggerEntry['available_items'])->first()['id']);
    }

    public function test_preview_craft_enchant_set_defaults_to_highest_valid_prefix_and_suffix(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createItemAffix(['name' => 'Cheap Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $expensivePrefix = $this->createItemAffix(['name' => 'Expensive Prefix', 'type' => 'prefix', 'cost' => 100, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createItemAffix(['name' => 'Cheap Suffix', 'type' => 'suffix', 'cost' => 5, 'int_required' => 0, 'skill_level_required' => 1]);
        $expensiveSuffix = $this->createItemAffix(['name' => 'Expensive Suffix', 'type' => 'suffix', 'cost' => 200, 'int_required' => 0, 'skill_level_required' => 1]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);

        $this->assertSame($expensivePrefix->id, $response->json('cost_breakdown.default_prefix_affix_id'));
        $this->assertSame($expensiveSuffix->id, $response->json('cost_breakdown.default_suffix_affix_id'));
    }

    public function test_preview_craft_enchant_set_default_affix_does_not_exceed_character_int(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $eligiblePrefix = $this->createItemAffix(['name' => 'Eligible Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createItemAffix(['name' => 'Too High Int Prefix', 'type' => 'prefix', 'cost' => 999, 'int_required' => 999999, 'skill_level_required' => 1]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);

        $this->assertSame($eligiblePrefix->id, $response->json('cost_breakdown.default_prefix_affix_id'));
    }

    public function test_preview_craft_enchant_set_default_affix_does_not_exceed_enchanting_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $eligibleSuffix = $this->createItemAffix(['name' => 'Eligible Suffix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createItemAffix(['name' => 'Too High Skill Suffix', 'type' => 'suffix', 'cost' => 999, 'int_required' => 0, 'skill_level_required' => 999]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);

        $this->assertSame($eligibleSuffix->id, $response->json('cost_breakdown.default_suffix_affix_id'));
    }

    public function test_start_craft_enchant_set_accepts_untouched_default_plan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $armour = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $ring = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $spell = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $character->skills()->createMany([['game_skill_id' => $armour->id, 'level' => 5], ['game_skill_id' => $ring->id, 'level' => 5], ['game_skill_id' => $spell->id, 'level' => 5]]);
        $this->createCraftableEquipmentItems(['cost' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createItemAffix(['name' => 'Default Plan Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createItemAffix(['name' => 'Default Plan Suffix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);

        $previewResponse = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'selected_set_id' => $set->id],
        ]);

        $defaultPrefixId = $previewResponse->json('cost_breakdown.default_prefix_affix_id');
        $defaultSuffixId = $previewResponse->json('cost_breakdown.default_suffix_affix_id');
        $keys = collect($previewResponse->json('cost_breakdown.plan_entries'))->pluck('key');
        $enchantPlan = $keys->mapWithKeys(fn (string $key) => [$key => [
            'prefix_affix_id' => $defaultPrefixId,
            'suffix_affix_id' => $defaultSuffixId,
        ]])->all();

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => $enchantPlan,
            ],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first());
    }

    public function test_start_craft_enchant_set_rejects_enchant_existing_mode(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $response = $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_mode' => 'enchant_existing',
                'enchant_plan' => [],
            ],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $response->assertStatus(302);
        $this->assertTrue(session()->has('errors'));
    }

    public function test_preview_craft_enchant_set_manual_selection_overrides_default_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 10]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $lowItem = $this->createItem(['name' => 'Manual Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createItem(['name' => 'Manual High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 50, 'skill_level_required' => 3, 'skill_level_trivial' => 1]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'enchant_plan' => [
                    'left_hand' => ['selected_item_id' => $lowItem->id],
                ],
            ],
        ]);

        $daggerEntry = collect($response->json('cost_breakdown.plan_entries'))->firstWhere('key', 'left_hand');

        $this->assertSame($lowItem->id, $daggerEntry['selected_item_id']);
    }

    public function test_start_craft_enchant_set_rejects_invalid_manual_affix_id(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $suffix = $this->createItemAffix(['name' => 'Wrong Type Affix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);

        $keys = collect($this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ])->json('cost_breakdown.plan_entries'))->pluck('key');

        $enchantPlan = $keys->mapWithKeys(fn (string $key) => [$key => [
            'prefix_affix_id' => $suffix->id,
        ]])->all();

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => $enchantPlan,
            ],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_craft_amount_uses_crafted_items_set_capacity_not_normal_inventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $item = $this->createItem(['name' => 'Destination Test Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1]);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
            ],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('may not exceed 0 for the selected destination', $response->json('errors')['progress.craft_amount'][0]);
    }

    public function test_craft_for_experience_uses_crafted_items_set_capacity_not_normal_inventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors')['batch_crafting'][0]);
    }

    public function test_craft_and_enchant_amount_uses_crafted_items_set_capacity_not_normal_inventory(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Destination Test Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Destination Test Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'sword',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('may not exceed 0 for the selected destination', $response->json('errors')['progress.craft_amount'][0]);
    }

    public function test_craft_and_enchant_for_experience_uses_crafted_items_set_capacity_not_normal_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $response = $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function test_trinketry_uses_crafted_items_set_capacity_not_normal_inventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100, 'copper_coins' => 100, 'shards' => 100]);
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $trinketry->id, 'character_id' => $character->id, 'level' => 1, 'xp' => 0, 'xp_max' => 100, 'is_locked' => false]);
        $this->createItem(['type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'trinket']);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [
                'batch_crafting',
            ],
        ]);
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function test_alchemy_uses_alchemy_bag_capacity_not_normal_inventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 0, 'gold_dust' => 1000]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function test_craft_set_ignores_selected_set_id_and_blocks_when_crafted_items_set_is_full(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);
        $targetSet = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $targetSet->id,
            ],
        ]);

        $response->assertStatus(422);
        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first());
        $this->assertSame(0, $targetSet->refresh()->slots()->count());
    }

    public function test_craft_and_enchant_set_build_new_ignores_selected_set_id_and_blocks_when_crafted_items_set_is_full(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);
        $this->createItemAffix(['name' => 'Selected Set Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createItemAffix(['name' => 'Selected Set Suffix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $targetSet = $this->createInventorySet(['character_id' => $character->id]);

        $previewResponse = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);
        $defaultPrefixId = $previewResponse->json('cost_breakdown.default_prefix_affix_id');
        $defaultSuffixId = $previewResponse->json('cost_breakdown.default_suffix_affix_id');
        $keys = collect($previewResponse->json('cost_breakdown.plan_entries'))->pluck('key');
        $enchantPlan = $keys->mapWithKeys(fn (string $key) => [$key => [
            'prefix_affix_id' => $defaultPrefixId,
            'suffix_affix_id' => $defaultSuffixId,
        ]])->all();

        $response = $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $targetSet->id,
                'enchant_plan' => $enchantPlan,
            ],
        ]);

        $response->assertStatus(422);
        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first());
        $this->assertSame(0, $targetSet->refresh()->slots()->count());
    }

    public function test_generic_inventory_space_message_not_shown_when_crafted_items_set_is_the_blocker(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->assertStringNotContainsString('cannot start without inventory space', $response->json('errors.batch_crafting.0'));
    }

    public function test_craft_for_experience_starts_with_zero_normal_inventory_space_since_destination_is_crafted_items_set(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 0, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $response->assertStatus(200);
        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_start_initializes_waiting_continuation_state_immediately(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $this->createSkill(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first();

        $this->assertSame('waiting', $batchCrafting->progress['continuation_state'] ?? null);
        $this->assertSame('starting', $batchCrafting->progress['continuation_phase'] ?? null);
        $this->assertNotNull($batchCrafting->progress['next_attempt_at'] ?? null);
    }

    public function test_start_craft_and_enchant_set_initializes_empty_finalized_and_lost_item_key_lists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $armour = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $ring = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $spell = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $character->skills()->createMany([['game_skill_id' => $armour->id, 'level' => 5], ['game_skill_id' => $ring->id, 'level' => 5], ['game_skill_id' => $spell->id, 'level' => 5]]);
        $this->createCraftableEquipmentItems(['cost' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Start Finalized Keys Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $processor = resolve(BatchCraftingProcessor::class);
        $keys = $processor->craftEnchantSetPlanKeys($processor->craftSetQueue());
        $plan = array_fill_keys($keys, ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_enchant_set', 'enchant_plan' => $plan],
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first();

        $this->assertSame([], $batchCrafting->progress['craft_enchant_set_finalized_keys'] ?? null);
        $this->assertSame([], $batchCrafting->progress['craft_enchant_set_lost_item_keys'] ?? null);
    }

    public function test_craft_experience_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function test_craft_and_enchant_experience_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'output_destination' => 'crafted_items_set'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_event_craft_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event', 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_event_enchant_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event', 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_alchemy_amount_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
        $item = $this->createItem(['name' => 'Validation Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id, 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_trinketry_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience', 'output_destination' => 'crafted_items_set'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_holy_oils_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $this->createSkill(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
        $item = $this->createItem(['name' => 'Validation Holy Oil Item', 'type' => 'weapon', 'holy_stacks' => 1]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $slot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Validation Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$slot->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'selected', 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_non_keep_finite_batch_rejects_supplied_output_destination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $item = $this->createItem(['name' => 'Validation Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function test_output_set_id_rejected_when_destination_is_not_inventory_set(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $item = $this->createItem(['name' => 'Validation Output Set Id Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'output_destination' => 'crafted_items_set', 'output_set_id' => $set->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_set_id']);
    }

    public function test_finite_craft_amount_keep_accepts_inventory_output_destination(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['inventory_max' => 10, 'gold' => 100]);
        $user = $character->user;
        $item = $this->createItem(['name' => 'Valid Amount Destination Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1]);

        $response = $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'output_destination' => 'inventory'],
        ]);

        $response->assertStatus(200);
        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();
        $this->assertSame('inventory', $batchCrafting->progress['output_destination'] ?? null);
    }
}
