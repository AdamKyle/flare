<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\InventorySet;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\SetSlot;
use App\Flare\Values\AutomationType;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Flare\Values\ItemSpecialtyType;
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
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateUser;

class BatchCraftingControllerTest extends TestCase
{
    use CreateAlchemyBag, CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacter, CreateCharacterAutomation, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateInventory, CreateInventorySlot, CreateItem, CreateItemAffix, CreateUser, RefreshDatabase;

    public function testStartCraftBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first());
    }

    public function testStartCraftAndEnchantBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first());
    }

    public function testCannotStartEnchantBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ENCHANT->value)->first());
    }

    public function testStartAlchemyBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function testStartAlchemyBatchRejectsLockedAlchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => true]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function testStartHolyOilsBatchRejectsLockedAlchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => true]);
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

    public function testStartAlchemyBatchRejectsSellDisposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function testStartHolyOilsBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
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

    public function testStartTrinketryBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::TRINKETRY->value)->first());
    }

    public function testRejectInvalidDisposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => 'invalid',
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectListForCraft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectListForHolyOils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectListForTrinketry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testAllowListForCraftAndEnchant(): void
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

    public function testRejectListForEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testAllowListForAlchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'listing_price' => 1,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function testAllowDisenchantForCraftAndEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::DISENCHANT->value)->first());
    }

    public function testRejectDisenchantForCraft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectKeepBestDisenchantRestForEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testCraftExperienceDoesNotRequireSpecificCraftingType(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();

        $this->assertSame('experience', $batchCrafting->progress['craft_mode'] ?? null);
        $this->assertArrayNotHasKey('set_count', $batchCrafting->progress ?? []);
    }

    public function testRejectFullSetCraftMode(): void
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

    public function testCraftAndEnchantSpecificItemStartsWithAmount(): void
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

    public function testCraftAndEnchantSpecificItemAcceptsOnePrefixAffix(): void
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

    public function testCraftAndEnchantSpecificItemAcceptsOneSuffixAffix(): void
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

    public function testCraftAndEnchantSpecificItemAcceptsPrefixAndSuffixAffixes(): void
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

    public function testAlchemyAmountRequiresSelectedItem(): void
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

    public function testRejectDisenchantForAlchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForHolyOils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForTrinketry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testKeepBestDestroyRestAllowedForCraftExperience(): void
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

    public function testKeepBestDestroyRestRejectedForCraftAmount(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $item = $this->createItem(['name' => 'Keep Best Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testTrinketryAllowsKeepBestDestroyRestDisposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value)->first());
    }

    public function testAlchemyAmountAllowsUseNowDisposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::USE_NOW->value)->first());
    }

    public function testStartRejectsListDispositionWithoutListingPrice(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testHolyOilsListRejectedWhenSelectedItemHasNoEnchants(): void
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

    public function testHolyOilsListAllowedWhenSelectedItemHasEnchants(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
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

    public function testCancelActiveBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('cancelled_at')->first());
    }

    public function testCannotStartDuplicateActiveBatch(): void
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

    public function testCannotStartWhileFactionLoyaltyIsRunning(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createCharacterAutomation(['character_id' => $character->id, 'type' => AutomationType::FACTION_LOYALTY]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testCanCoexistWithExplorationWhereExistingRulesAllowIt(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $this->createCharacterAutomation(['character_id' => $character->id, 'type' => AutomationType::EXPLORING]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsWithoutSelectedItems(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsWithoutSelectedOils(): void
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

    public function testRejectHolyOilsItemNotOwnedByCharacter(): void
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

    public function testRejectHolyOilsOilNotOwnedByCharacter(): void
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

    public function testRejectHolyOilsIneligibleItem(): void
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

    public function testRejectHolyOilsInvalidOilStack(): void
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

    public function testRejectCraftExperienceWhenAllCraftingSkillsMaxed(): void
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

    public function testAllowCraftExperienceStartWhenSubmittedSkillIsMaxedButAnotherSkillIsNot(): void
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

    public function testCraftAndEnchantExperienceStartsWhenOnlyEnchantingIsMaxed(): void
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

    public function testRejectCraftAndEnchantExperienceWhenAllCraftingSkillsAndEnchantingAreMaxed(): void
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

    public function testRejectAlchemyExperienceWhenAlchemyIsMaxed(): void
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

    public function testRejectTrinketryWhenTrinketryIsMaxed(): void
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

    public function testDoNotRejectEventCraftingWhenCraftingSkillsMaxed(): void
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
        $schedule = ScheduledEvent::factory()->create(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
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

    public function testDoNotRejectEventEnchantingWhenEnchantingIsMaxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->update(['level' => 5]);
        $schedule = ScheduledEvent::factory()->create(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $character = $character->refresh();

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testCancelIsIdempotentWhenNoBatchRunning(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testPreviewReturnsCraftAmountDataBeforeBatchIsStarted(): void
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

    public function testPreviewCraftAndEnchantSetReturnsNonZeroEnchantCostThroughHttpValidation(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $this->createInventory(['character_id' => $character->id]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $this->createItem(['name' => 'Preview Set Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = InventorySet::factory()->create(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['name' => 'HTTP Preview Prefix', 'type' => 'prefix', 'cost' => 30, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'HTTP Preview Suffix', 'type' => 'suffix', 'cost' => 40, 'int_required' => 0, 'skill_level_required' => 1]);

        $response = $this->actingAs($user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'enchant_plan' => [
                    'dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id],
                ],
            ],
        ]);

        $this->assertSame(70, $response->json('cost_breakdown.enchant_cost_total'));
    }

    public function testPreviewCraftEnchantSetReturnsAllTwentyThreeSetTargets(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $response = $this->actingAs($character->user)->call('POST', route('batch-crafting.preview', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'progress' => ['craft_mode' => 'craft_enchant_set'],
        ]);

        $this->assertCount(23, $response->json('cost_breakdown.plan_entries'));
    }

    public function testPreviewCraftEnchantSetDefaultsToHighestCraftableItemPerTarget(): void
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

        $daggerEntry = collect($response->json('cost_breakdown.plan_entries'))->firstWhere('key', 'dagger');

        $this->assertSame($highItem->id, $daggerEntry['selected_item_id']);
    }

    public function testPreviewCraftEnchantSetDefaultsToHighestValidPrefixAndSuffix(): void
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

    public function testPreviewCraftEnchantSetDefaultAffixDoesNotExceedCharacterInt(): void
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

    public function testPreviewCraftEnchantSetDefaultAffixDoesNotExceedEnchantingSkill(): void
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

    public function testStartCraftEnchantSetAcceptsUntouchedDefaultPlan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $set = InventorySet::factory()->create(['character_id' => $character->id]);
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

    public function testStartCraftEnchantSetRejectsEnchantExistingMode(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);

        $this->actingAs($character->user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_mode' => 'enchant_existing',
                'enchant_plan' => [],
            ],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->response->assertStatus(302);
        $this->assertTrue(session()->has('errors'));
    }

    public function testPreviewCraftEnchantSetManualSelectionOverridesDefaultItem(): void
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
                    'dagger' => ['selected_item_id' => $lowItem->id],
                ],
            ],
        ]);

        $daggerEntry = collect($response->json('cost_breakdown.plan_entries'))->firstWhere('key', 'dagger');

        $this->assertSame($lowItem->id, $daggerEntry['selected_item_id']);
    }

    public function testStartCraftEnchantSetRejectsInvalidManualAffixId(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $set = InventorySet::factory()->create(['character_id' => $character->id]);
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

    public function testCraftAmountUsesCraftedItemsSetCapacityNotNormalInventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 1000]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $item = $this->createItem(['name' => 'Destination Test Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1]);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1,
            ],
        ]);
        $response = $this->response;

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function testCraftForExperienceUsesCraftedItemsSetCapacityNotNormalInventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $response = $this->response;

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function testCraftAndEnchantAmountUsesCraftedItemsSetCapacityNotNormalInventory(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Destination Test Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Destination Test Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $item->id]);

        $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
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
        $response = $this->response;

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function testCraftAndEnchantForExperienceUsesCraftedItemsSetCapacityNotNormalInventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 100, 'inventory_max' => 10]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $response = $this->response;

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function testTrinketryUsesCraftedItemsSetCapacityNotNormalInventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'trinket']);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);
        $response = $this->response;

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
        $this->assertStringContainsString('Crafted Items Set is full', $response->json('errors.batch_crafting.0'));
    }

    public function testAlchemyUsesAlchemyBagCapacityNotNormalInventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 0, 'gold_dust' => 1000]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);
        $response = $this->response;

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function testCraftSetIgnoresSelectedSetIdAndBlocksWhenCraftedItemsSetIsFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);
        $targetSet = InventorySet::factory()->create(['character_id' => $character->id]);

        $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $targetSet->id,
            ],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first());
        $this->assertSame(0, $targetSet->refresh()->slots()->count());
    }

    public function testCraftAndEnchantSetBuildNewIgnoresSelectedSetIdAndBlocksWhenCraftedItemsSetIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);
        $this->createItemAffix(['name' => 'Selected Set Prefix', 'type' => 'prefix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $this->createItemAffix(['name' => 'Selected Set Suffix', 'type' => 'suffix', 'cost' => 10, 'int_required' => 0, 'skill_level_required' => 1]);
        $targetSet = InventorySet::factory()->create(['character_id' => $character->id]);

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

        $this->actingAs($character->user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $targetSet->id,
                'enchant_plan' => $enchantPlan,
            ],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $this->assertNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first());
        $this->assertSame(0, $targetSet->refresh()->slots()->count());
    }

    public function testGenericInventorySpaceMessageNotShownWhenCraftedItemsSetIsTheBlocker(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);
        $filler = $this->createItem(['name' => 'Filler Item', 'type' => 'dagger']);
        $craftedItemsSet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 1,
        ]);
        SetSlot::create(['inventory_set_id' => $craftedItemsSet->id, 'item_id' => $filler->id]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $response = $this->response;

        $this->assertStringNotContainsString('cannot start without inventory space', $response->json('errors.batch_crafting.0'));
    }

    public function testCraftForExperienceStartsWithZeroNormalInventorySpaceSinceDestinationIsCraftedItemsSet(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 0, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $response = $this->response;

        $response->assertStatus(200);
        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testStartInitializesWaitingContinuationStateImmediately(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $batchCrafting = BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first();

        $this->assertSame('waiting', $batchCrafting->progress['continuation_state'] ?? null);
        $this->assertSame('starting', $batchCrafting->progress['continuation_phase'] ?? null);
        $this->assertNotNull($batchCrafting->progress['next_attempt_at'] ?? null);
    }

    public function testStartCraftAndEnchantSetInitializesEmptyFinalizedAndLostItemKeyLists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
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

    public function testCraftExperienceRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testCraftAndEnchantExperienceRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'output_destination' => 'crafted_items_set'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testEventCraftRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event', 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testEventEnchantRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event', 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testAlchemyAmountRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100, 'shards' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
        $item = $this->createItem(['name' => 'Validation Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_item_id' => $item->id, 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testTrinketryRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience', 'output_destination' => 'crafted_items_set'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testHolyOilsRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemy = $this->createGameSkill(['name' => 'Alchemy', 'type' => SkillTypeValue::ALCHEMY->value, 'max_level' => 400]);
        $character->skills()->create(['game_skill_id' => $alchemy->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100, 'is_locked' => false]);
        $item = $this->createItem(['name' => 'Validation Holy Oil Item', 'type' => 'weapon', 'holy_stacks' => 1]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $slot = $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Validation Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$slot->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => ['holy_oil_mode' => 'selected', 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testNonKeepFiniteBatchRejectsSuppliedOutputDestination(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $item = $this->createItem(['name' => 'Validation Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_destination']);
    }

    public function testOutputSetIdRejectedWhenDestinationIsNotInventorySet(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $item = $this->createItem(['name' => 'Validation Output Set Id Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1]);
        $set = InventorySet::factory()->create(['character_id' => $character->id]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'output_destination' => 'crafted_items_set', 'output_set_id' => $set->id],
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress.output_set_id']);
    }

    public function testFiniteCraftAmountKeepAcceptsInventoryOutputDestination(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['inventory_max' => 10, 'gold' => 100]);
        $user = $character->user;
        $item = $this->createItem(['name' => 'Valid Amount Destination Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1]);

        $this->actingAs($user)->json('POST', route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'output_destination' => 'inventory'],
        ]);
        $response = $this->response;

        $response->assertStatus(200);
        $batchCrafting = BatchCrafting::where('character_id', $character->id)->first();
        $this->assertSame('inventory', $batchCrafting->progress['output_destination'] ?? null);
    }
}
