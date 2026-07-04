<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Values\AutomationType;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
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

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function testStartHolyOilsBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
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

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
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
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $otherAlchemyBag->id, 'character_id' => $otherCharacter->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
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
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
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
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
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
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
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
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
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
}
