<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Game\Gems\Services\GemAbilityDuplicateService;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemSocket;

class GemAbilityDuplicateServiceTest extends TestCase
{
    use CreateCharacterGemModifier;
    use CreateGameGemAbility;
    use CreateGem;
    use CreateInventorySets;
    use CreateItem;
    use CreateItemSocket;
    use RefreshDatabase;

    public function test_owned_backpack_item_with_two_copies_returns_one_item_and_two_occurrences(): void
    {
        $ability = $this->createGameGemAbility(['name' => 'Flames of the Heretic']);
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $item = $this->createItem(['type' => 'body', 'socket_count' => 2]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $secondGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $duplicates = (new GemAbilityDuplicateService)->forOwnedItem($character->id, $item->id)->toArray();

        $this->assertCount(1, $duplicates);
        $this->assertSame(2, $duplicates[0]['count']);
        $this->assertCount(1, $duplicates[0]['items']);
        $this->assertSame($item->id, $duplicates[0]['items'][0]['item_id']);
        $this->assertSame('inventory', $duplicates[0]['items'][0]['source']);
    }

    public function test_owned_backpack_and_equipped_items_return_their_exact_sources(): void
    {
        $ability = $this->createGameGemAbility();
        $backpackGem = $this->createGem();
        $equippedGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $backpackGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $equippedGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $backpackItem = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $equippedItem = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $backpackItem->id, 'gem_id' => $backpackGem->id]);
        $this->createItemSocket(['item_id' => $equippedItem->id, 'gem_id' => $equippedGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($backpackItem)
            ->giveItem($equippedItem, true, 'helmet')
            ->getCharacter();

        $duplicates = (new GemAbilityDuplicateService)->forOwnedItem($character->id, $backpackItem->id)->toArray();
        $items = collect($duplicates[0]['items'])->keyBy('item_id');

        $this->assertSame('inventory', $items[$backpackItem->id]['source']);
        $this->assertSame('equipped', $items[$equippedItem->id]['source']);
        $this->assertNull($items[$equippedItem->id]['set_id']);
        $this->assertNull($items[$equippedItem->id]['set_name']);
    }

    public function test_two_items_in_an_unequipped_set_return_set_location_metadata(): void
    {
        $ability = $this->createGameGemAbility();
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $firstItem = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $secondItem = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $firstItem->id, 'gem_id' => $firstGem->id]);
        $this->createItemSocket(['item_id' => $secondItem->id, 'gem_id' => $secondGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Raid Gear',
            'is_equipped' => false,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $firstItem->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $secondItem->id]);

        $duplicates = (new GemAbilityDuplicateService)->forInventorySet($character->id, $set->id)->toArray();

        $this->assertCount(1, $duplicates);
        $this->assertSame(['set', 'set'], array_column($duplicates[0]['items'], 'source'));
        $this->assertSame([$set->id, $set->id], array_column($duplicates[0]['items'], 'set_id'));
        $this->assertSame(['Raid Gear', 'Raid Gear'], array_column($duplicates[0]['items'], 'set_name'));
    }

    public function test_backpack_item_warning_includes_matching_item_in_an_unequipped_set(): void
    {
        $ability = $this->createGameGemAbility();
        $backpackGem = $this->createGem();
        $setGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $backpackGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $setGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $backpackItem = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $setItem = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $backpackItem->id, 'gem_id' => $backpackGem->id]);
        $this->createItemSocket(['item_id' => $setItem->id, 'gem_id' => $setGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($backpackItem)
            ->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Raid Gear',
            'is_equipped' => false,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $setItem->id]);

        $duplicates = (new GemAbilityDuplicateService)->forOwnedItem($character->id, $backpackItem->id)->toArray();
        $items = collect($duplicates[0]['items'])->keyBy('item_id');

        $this->assertSame('inventory', $items[$backpackItem->id]['source']);
        $this->assertSame('set', $items[$setItem->id]['source']);
        $this->assertSame($set->id, $items[$setItem->id]['set_id']);
        $this->assertSame('Raid Gear', $items[$setItem->id]['set_name']);
    }

    public function test_selected_set_warning_includes_matching_owned_item_outside_the_set(): void
    {
        $ability = $this->createGameGemAbility();
        $setGem = $this->createGem();
        $externalGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $setGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $externalGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $setItem = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $externalItem = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $setItem->id, 'gem_id' => $setGem->id]);
        $this->createItemSocket(['item_id' => $externalItem->id, 'gem_id' => $externalGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($externalItem)
            ->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Raid Gear',
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $setItem->id]);

        $duplicates = (new GemAbilityDuplicateService)->forInventorySet($character->id, $set->id)->toArray();
        $affectedItemIds = array_column($duplicates[0]['items'], 'item_id');
        sort($affectedItemIds);
        $expectedItemIds = [$setItem->id, $externalItem->id];
        sort($expectedItemIds);

        $this->assertCount(1, $duplicates);
        $this->assertSame($expectedItemIds, $affectedItemIds);
    }

    public function test_single_item_report_returns_duplicate_copies_socketed_in_that_item(): void
    {
        $ability = $this->createGameGemAbility(['name' => 'Flames of the Heretic']);
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $item = $this->createItem(['type' => 'body', 'socket_count' => 2]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $secondGem->id]);

        $duplicates = (new GemAbilityDuplicateService)->forItem($item->id)->toArray();

        $this->assertSame([
            'id' => $ability->id,
            'name' => 'Flames of the Heretic',
        ], $duplicates[0]['ability']);
        $this->assertSame(2, $duplicates[0]['count']);
        $this->assertSame('inventory', $duplicates[0]['items'][0]['source']);
    }

    public function test_different_active_abilities_do_not_return_duplicate_groups(): void
    {
        $firstAbility = $this->createGameGemAbility();
        $secondAbility = $this->createGameGemAbility();
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $firstAbility->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $secondAbility->id,
        ]);
        $item = $this->createItem(['type' => 'body', 'socket_count' => 2]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $secondGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $duplicates = (new GemAbilityDuplicateService)->forOwnedItem($character->id, $item->id)->toArray();

        $this->assertSame([], $duplicates);
    }
}
