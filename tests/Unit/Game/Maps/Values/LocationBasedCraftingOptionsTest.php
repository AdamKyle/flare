<?php

namespace Tests\Unit\Game\Maps\Values;

use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Values\LocationBasedCraftingOptions;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;

class LocationBasedCraftingOptionsTest extends TestCase
{
    use CreateGameMap, CreateItem, RefreshDatabase;

    public function test_can_use_work_bench_is_true_in_purgatory(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::PURGATORY->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertTrue($locationBasedCraftingOptions->canUseWorkBench);
    }

    public function test_can_use_work_bench_is_false_outside_purgatory(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertFalse($locationBasedCraftingOptions->canUseWorkBench);
    }

    public function test_can_use_queen_of_hearts_is_true_in_hell_with_required_item(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::HELL->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)
            ->inventoryManagement()
            ->giveItem($this->createItem(['effect' => ItemEffectType::QUEEN_OF_HEARTS->value]))
            ->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertTrue($locationBasedCraftingOptions->canUseQueenOfHearts);
    }

    public function test_can_use_queen_of_hearts_is_false_in_hell_without_required_item(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::HELL->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertFalse($locationBasedCraftingOptions->canUseQueenOfHearts);
    }

    public function test_can_use_queen_of_hearts_is_false_outside_hell_with_required_item(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)
            ->inventoryManagement()
            ->giveItem($this->createItem(['effect' => ItemEffectType::QUEEN_OF_HEARTS->value]))
            ->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertFalse($locationBasedCraftingOptions->canUseQueenOfHearts);
    }

    public function test_can_access_labyrinth_oracle_is_true_in_labyrinth(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::LABYRINTH->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertTrue($locationBasedCraftingOptions->canAccessLabyrinthOracle);
    }

    public function test_can_access_labyrinth_oracle_is_false_outside_labyrinth(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertFalse($locationBasedCraftingOptions->canAccessLabyrinthOracle);
    }

    public function test_can_access_seer_camp_is_true_in_purgatory(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::PURGATORY->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertTrue($locationBasedCraftingOptions->canAccessSeerCamp);
    }

    public function test_can_access_seer_camp_is_false_outside_purgatory(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::SURFACE->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertFalse($locationBasedCraftingOptions->canAccessSeerCamp);
    }

    public function test_can_access_seer_camp_is_false_when_map_is_missing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertFalse($locationBasedCraftingOptions->canAccessSeerCamp);
    }
}
