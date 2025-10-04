<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EnchantItemServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?EnchantItemService $enchantItemService;

    private ?Item $itemToEnchant;

    private ?ItemAffix $suffix;

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

        $this->enchantItemService = resolve(EnchantItemService::class);

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
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->enchantingSkill = null;
        $this->enchantItemService = null;
        $this->suffix = null;
        $this->itemToEnchant = null;
    }

    public function test_enchant_the_item_when_too_easy()
    {

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);

        $item = $this->enchantItemService->getItem();

        $this->assertNotNull($item->item_suffix_id);
    }

    public function test_enchant_the_item_with_a_prefix_when_too_easy()
    {

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->createItemAffix([
            'type' => 'prefix',
        ]), $skill, true);

        $item = $this->enchantItemService->getItem();

        $this->assertNotNull($item->item_suffix_id);
        $this->assertNotNull($item->item_prefix_id);
    }

    public function test_enchant_the_item_with_a_prefix_that_is_mythic_and_expect_the_prefix_to_not_be_attached_when_too_easy()
    {

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->itemToEnchant->update([
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'name' => 'Non Mythical',
            ])->id,
        ]);

        $itemToEnchant = $this->itemToEnchant->refresh();

        $this->enchantItemService->attachAffix($itemToEnchant, $this->createItemAffix([
            'type' => 'prefix',
            'name' => 'Mythical',
            'cost' => RandomAffixDetails::MYTHIC,
        ]), $skill, true);

        $item = $this->enchantItemService->getItem();

        $this->assertNull($item->item_prefix_id);
    }

    public function test_enchant_the_item_with_a_prefix_that_is_cosmic_and_expect_the_prefix_to_not_be_attached_when_too_easy()
    {

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->itemToEnchant->update([
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'name' => 'Non Cosmic',
            ])->id,
        ]);

        $itemToEnchant = $this->itemToEnchant->refresh();

        $this->enchantItemService->attachAffix($itemToEnchant, $this->createItemAffix([
            'type' => 'prefix',
            'name' => 'Cosmic',
            'cost' => RandomAffixDetails::COSMIC,
        ]), $skill, true);

        $item = $this->enchantItemService->getItem();

        $this->assertNull($item->item_prefix_id);
    }

    public function test_enchant_the_item_with_a_suffix_that_is_mythic_and_expect_the_prefix_to_not_be_attached_when_too_easy()
    {

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->itemToEnchant->update([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'name' => 'Non Mythical',
            ])->id,
        ]);

        $itemToEnchant = $this->itemToEnchant->refresh();

        $this->enchantItemService->attachAffix($itemToEnchant, $this->createItemAffix([
            'type' => 'suffix',
            'name' => 'Mythical',
            'cost' => RandomAffixDetails::MYTHIC,
        ]), $skill, true);

        $item = $this->enchantItemService->getItem();

        $this->assertNull($item->item_suffix_id);
    }

    public function test_enchant_the_item_with_a_suffix_that_is_cosmic_and_expect_the_prefix_to_not_be_attached_when_too_easy()
    {

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->itemToEnchant->update([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'name' => 'Non Mythical',
            ])->id,
        ]);

        $itemToEnchant = $this->itemToEnchant->refresh();

        $this->enchantItemService->attachAffix($itemToEnchant, $this->createItemAffix([
            'type' => 'suffix',
            'name' => 'Mythical',
            'cost' => RandomAffixDetails::COSMIC,
        ]), $skill, true);

        $item = $this->enchantItemService->getItem();

        $this->assertNull($item->item_suffix_id);
    }

    public function test_enchant_the_item_with_dc_check()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $enchantItemService = $this->app->make(EnchantItemService::class);

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, false);

        $item = $enchantItemService->getItem();

        $this->assertNotNull($item->item_suffix_id);
    }

    public function test_fail_to_enchant_the_item_with_dc_check()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $enchantItemService = $this->app->make(EnchantItemService::class);

        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $result = $enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, false);

        $item = $enchantItemService->getItem();

        $this->assertNull($item);
        $this->assertFalse($result);
    }

    public function test_update_the_characters_inventory_slot()
    {

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->updateSlot($slot, false);

        $item = $this->enchantItemService->getItem();

        $this->assertNotNull($item->item_suffix_id);
        $this->assertEquals($slot->refresh()->item_id, $item->id);
    }

    public function test_update_the_characters_inventory_slot_when_there_are_attached_holy_stacks()
    {

        $this->itemToEnchant->appliedHolyStacks()->create([
            'item_id' => $this->itemToEnchant->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToEnchant = $this->itemToEnchant->refresh();

        $character = $this->character->inventoryManagement()->giveItem($itemToEnchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->updateSlot($slot, false);

        $item = $this->enchantItemService->getItem();

        $this->assertNotNull($item->item_suffix_id);
        $this->assertEquals($slot->refresh()->item_id, $item->id);
        $this->assertCount(1, $item->appliedHolyStacks);
    }

    public function test_update_the_characters_inventory_slot_when_there_are_sockets_attached()
    {

        $this->itemToEnchant->sockets()->create([
            'item_id' => $this->itemToEnchant->id,
            'gem_id' => $this->createGem()->id,
        ]);

        $this->itemToEnchant->update([
            'socket_count' => 1,
        ]);

        $itemToEnchant = $this->itemToEnchant->refresh();

        $character = $this->character->inventoryManagement()->giveItem($itemToEnchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->updateSlot($slot, false);

        $item = $this->enchantItemService->getItem();

        $this->assertNotNull($item->item_suffix_id);
        $this->assertEquals($slot->refresh()->item_id, $item->id);
        $this->assertCount(1, $item->sockets);
    }

    public function test_update_the_characters_inventory_slot_with_matching_item_when_there_are_duplicate_items()
    {

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $duplicateItem = $this->createItem([
            'name' => $this->itemToEnchant->name,
            'item_suffix_id' => $this->suffix->id,
        ]);

        $slot = $character->inventory->slots->first();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->updateSlot($slot, false);

        $item = $this->enchantItemService->getItem();

        // we found a duplicate so we deleted this item and set it to null.
        $this->assertNull($item);

        // Because there are duplicates, we take the first one that matches the newly enchanted item.
        $this->assertEquals($slot->refresh()->item_id, $duplicateItem->id);
    }

    public function test_update_character_slot_with_non_duplicate_when_item_to_enchant_has_holy_stacks()
    {

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $duplicateItem = $this->createItem([
            'name' => $this->itemToEnchant->name,
            'item_suffix_id' => $this->suffix->id,
        ]);

        $slot = $character->inventory->slots->first();

        $itemToEnchant = $this->itemToEnchant;

        $itemToEnchant->appliedHolyStacks()->create([
            'item_id' => $itemToEnchant,
            'devouring_darkness_bonus' => 0.01,
            'stat_increase_bonus' => 0.1,
        ]);

        $itemToEnchant = $itemToEnchant->refresh();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->updateSlot($slot, false);

        $item = $this->enchantItemService->getItem();

        $this->assertNotNull($item);

        $slot = $slot->refresh();

        // There may be a duplicate item, but the item to enchant has holy stacks applied.
        $this->assertNotEquals($slot->item_id, $duplicateItem->id);
        $this->assertEquals($slot->item_id, $item->id);
    }

    public function test_delete_the_slot()
    {

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $skill = $character->skills->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->enchantItemService->attachAffix($this->itemToEnchant, $this->suffix, $skill, true);
        $this->enchantItemService->deleteSlot($slot);

        $item = $this->enchantItemService->getItem();

        $this->assertNull($item);
        $this->assertTrue($character->refresh()->inventory->slots->isEmpty());
    }
}
