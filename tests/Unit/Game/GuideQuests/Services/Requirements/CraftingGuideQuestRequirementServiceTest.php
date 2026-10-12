<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\GuideQuests\Services\Requirements\CraftingGuideQuestRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftingGuideQuestRequirementServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateGuideQuest, CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CraftingGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new CraftingGuideQuestRequirementService;
    }

    public function test_running_craft_experience_batch_at_required_hours_passes(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft',
            'started_at' => now()->subHours(2),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'craft_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function test_cancelled_alchemy_experience_batch_at_required_hours_passes(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'alchemy',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'alchemy',
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHour(),
            'cancelled_at' => now()->subHour(),
            'progress' => [
                'alchemy_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function test_cancelled_matching_batch_before_required_hours_fails(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'alchemy',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'alchemy',
            'started_at' => now()->subMinutes(90),
            'completed_at' => now(),
            'cancelled_at' => now(),
            'progress' => [
                'alchemy_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function test_matching_type_with_non_experience_mode_fails(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'craft_mode' => 'gold',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function test_trinketry_experience_batch_at_required_hours_passes(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'trinketry',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'trinketry',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'trinketry_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function test_plain_dagger_inventory_requirement_passes_with_enough_unenchanted_matching_items(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);

        $character = $this->character->inventoryManagement()->giveItem($dagger)->getCharacter();
        $character = $this->character->inventoryManagement()->giveItem($dagger)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_plain_dagger_inventory_requirement_does_not_count_enchanted_daggers(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedDagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'parent_id' => $dagger->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $character = $this->character->inventoryManagement()->giveItem($enchantedDagger)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_enchanted_helmet_inventory_requirement_passes_when_enough_matching_enchanted_helmets_exist(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 2, 'must_be_enchanted' => true],
            ],
        ]);

        $character = $this->character->inventoryManagement()->giveItem($enchantedHelmet)->getCharacter();
        $character = $this->character->inventoryManagement()->giveItem($enchantedHelmet)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_enchanted_helmet_inventory_requirement_fails_when_matching_items_have_only_prefix(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $prefixedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);

        $character = $this->character->inventoryManagement()->giveItem($prefixedHelmet)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_player_facing_requirement_data_includes_item_name_type_amount_and_enchanted_state(): void
    {
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 10, 'must_be_enchanted' => true],
            ],
        ]);

        $displayRows = $guideQuest->required_batch_crafted_item_names;

        $this->assertSame('Iron Helmet', $displayRows[0]['name']);
        $this->assertSame('helmet', $displayRows[0]['type']);
        $this->assertSame('Helmet', $displayRows[0]['type_name']);
        $this->assertSame(10, $displayRows[0]['amount']);
        $this->assertTrue($displayRows[0]['must_be_enchanted']);
    }

    public function test_configured_inventory_rows_return_independent_completion_statuses_and_factual_amounts(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $mace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $chest = $this->createItem(['name' => "Paladin's Oath Chest", 'type' => 'body', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedMace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'parent_id' => $mace->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $enchantedChest = $this->createItem(['name' => "Paladin's Oath Chest", 'type' => 'body', 'parent_id' => $chest->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);

        $this->character->inventoryManagement()->giveItemMultipleTimes($enchantedMace, 24);
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($enchantedChest, 14)->getCharacter();

        $requirements = $this->guideQuestRequirementsService->batchCraftedItemRequirements($character->refresh(), [
            ['source' => 'inventory', 'item_id' => $mace->id, 'amount' => 20, 'must_be_enchanted' => true],
            ['source' => 'inventory', 'item_id' => $chest->id, 'amount' => 15, 'must_be_enchanted' => true],
        ]);

        $this->assertCount(2, $requirements);
        $this->assertSame(0, $requirements[0]['requirement_index']);
        $this->assertSame(24, $requirements[0]['current_amount']);
        $this->assertTrue($requirements[0]['is_complete']);
        $this->assertSame(1, $requirements[1]['requirement_index']);
        $this->assertSame(14, $requirements[1]['current_amount']);
        $this->assertFalse($requirements[1]['is_complete']);
    }

    public function test_normal_inventory_is_checked_not_crafted_items_set(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $inventorySet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => 'batch_crafting']);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $this->createInventorySetSlot(['inventory_set_id' => $inventorySet->id, 'item_id' => $dagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_alchemy_bag_requirement_passes_when_the_bag_has_enough_amount(): void
    {
        $character = $this->character->getCharacter();
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 5, 'must_be_enchanted' => false],
            ],
        ]);

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $potion->id,
            'amount' => 5,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_mixed_inventory_plus_alchemy_requirements_pass_when_both_rows_are_satisfied(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);

        $character = $this->character->inventoryManagement()->giveItem($dagger)->getCharacter();
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $potion->id,
            'amount' => 3,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function test_mixed_inventory_plus_alchemy_requirements_fail_when_the_alchemy_row_is_short(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);

        $character = $this->character->inventoryManagement()->giveItem($dagger)->getCharacter();
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $potion->id,
            'amount' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }
}
