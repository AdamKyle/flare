<?php

namespace Tests\Unit\Game\Character\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameUnit;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Skill;
use App\Flare\Models\SmeltingProgress;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Character\Services\CharacterDeletion;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;
use Tests\Traits\CreateFactionLoyaltyAutomation;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMarketBoardListing;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateSmeltingProgress;

class CharacterDeletionTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateCelestials, CreateDelveAutomation, CreateExplorationLog, CreateExplorationWarning, CreateFactionLoyaltyAutomation, CreateGlobalCraftingInventory, CreateGlobalEventGoal, CreateItem, CreateMarketBoardListing, CreateMonster, CreateNpc, CreateQuest, CreateSmeltingProgress, RefreshDatabase;

    private ?CharacterFactory $characterFactory;

    private ?CharacterDeletion $characterDeletion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacterFactory();

        $this->createNpc([
            'game_map_id' => GameMap::first()->id,
            'type' => NpcType::KINGDOM_HOLDER->value,
        ]);

        $this->characterDeletion = resolve(CharacterDeletion::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->characterDeletion = null;
    }

    public function test_character_is_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(Character::find($character->id));
    }

    public function test_character_owned_rows_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();

        $this->createExplorationLog(['character_id' => $character->id]);
        $this->createExplorationWarning(['character_id' => $character->id]);
        $this->createSmeltingProgress(['character_id' => $character->id]);

        $monster = $this->createMonster(['is_celestial_entity' => true]);
        $celestialFight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => null,
            'conjured_at' => now(),
            'x_position' => 16,
            'y_position' => 16,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);
        $this->createCharacterInCelestialFight([
            'character_id' => $character->id,
            'celestial_fight_id' => $celestialFight->id,
            'character_max_health' => 1000,
            'character_current_health' => 500,
        ]);

        $characterId = $character->id;
        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertEquals(0, ExplorationLog::where('character_id', $characterId)->count());
        $this->assertEquals(0, ExplorationWarning::where('character_id', $characterId)->count());
        $this->assertEquals(0, CharacterInCelestialFight::where('character_id', $characterId)->count());
        $this->assertEquals(0, SmeltingProgress::where('character_id', $characterId)->count());
    }

    public function test_delve_exploration_and_logs_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();

        $exploration = $this->createDelveAutomation(['character_id' => $character->id]);
        $this->createDelveAutomationLog(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertEquals(0, DelveExploration::where('character_id', $character->id)->count());
    }

    public function test_items_remain_after_character_deletion(): void
    {
        $character = $this->characterFactory->getCharacter();
        $item = $this->createItem();

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNotNull(Item::find($item->id));
    }

    public function test_item_link_rows_are_deleted_but_items_remain(): void
    {
        $item = $this->createItem();

        $inventoryManagement = $this->characterFactory->inventoryManagement()->giveItem($item);
        $inventorySlotId = $inventoryManagement->getSlotId(0);
        $character = $inventoryManagement->getCharacter();

        $this->assertNotNull(InventorySlot::find($inventorySlotId));

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(InventorySlot::find($inventorySlotId));
        $this->assertNotNull(Item::find($item->id));
        $this->assertNull(Character::find($character->id));
    }

    public function test_kingdoms_transfer_to_npc_and_are_not_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();

        $this->assertGreaterThan(0, $character->kingdoms->count());
        $kingdomId = $character->kingdoms->first()->id;

        $this->characterDeletion->deleteCharacterFromUser($character);

        $kingdom = Kingdom::find($kingdomId);
        $this->assertNotNull($kingdom);
        $this->assertTrue($kingdom->npc_owned);
        $this->assertNull($kingdom->character_id);
    }

    public function test_skills_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $characterId = $character->id;

        $this->assertGreaterThan(0, $character->skills->count());

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertEquals(0, Skill::where('character_id', $characterId)->count());
    }

    public function test_passive_skills_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $characterId = $character->id;

        $this->assertGreaterThan(0, $character->passiveSkills->count());

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertEquals(0, CharacterPassiveSkill::where('character_id', $characterId)->count());
    }

    public function test_base_static_game_records_remain_after_character_deletion(): void
    {
        $character = $this->characterFactory->getCharacter();

        $gameClassId = $character->class->id;
        $gameRaceId = $character->race->id;
        $gameMapId = GameMap::first()->id;
        $passiveSkillId = PassiveSkill::first()->id;
        $gameBuildingId = GameBuilding::first()->id;
        $gameUnitId = GameUnit::first()->id;
        $monsterId = $this->createMonster()->id;
        $questItem = $this->createItem();
        $questId = $this->createQuest([
            'npc_id' => Npc::first()->id,
            'item_id' => $questItem->id,
        ])->id;
        $globalEventGoalId = $this->createGlobalEventGoal([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
        ])->id;

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNotNull(GameClass::find($gameClassId));
        $this->assertNotNull(GameRace::find($gameRaceId));
        $this->assertNotNull(GameMap::find($gameMapId));
        $this->assertNotNull(PassiveSkill::find($passiveSkillId));
        $this->assertNotNull(GameBuilding::find($gameBuildingId));
        $this->assertNotNull(GameUnit::find($gameUnitId));
        $this->assertNotNull(Monster::find($monsterId));
        $this->assertNotNull(Quest::find($questId));
        $this->assertNotNull(GlobalEventGoal::find($globalEventGoalId));
    }

    public function test_deleting_with_params_creates_a_new_character_for_the_user(): void
    {
        $character = $this->characterFactory->getCharacter();
        $user = $character->user;

        $this->createItem(['type' => 'sword', 'skill_level_required' => 1]);

        $this->characterDeletion->deleteCharacterFromUser($character, [
            'race_id' => $character->race->id,
            'class_id' => $character->class->id,
            'name' => 'A Brand New Name',
            'guide' => false,
        ]);

        $newCharacter = $user->refresh()->character;

        $this->assertNotNull($newCharacter);
        $this->assertSame('A Brand New Name', $newCharacter->name);
        $this->assertNotSame($character->id, $newCharacter->id);
        $this->assertFalse($user->refresh()->guide_enabled);
    }

    public function test_market_listings_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $item = $this->createItem();

        $listing = $this->createMarketBoardListing([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'listed_price' => 100,
        ]);

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(MarketBoard::find($listing->id));
    }

    public function test_gem_bag_slots_are_deleted(): void
    {
        $inventoryManagement = $this->characterFactory->gemBagManagement()->assignGemsToBag(1, 1);
        $character = $inventoryManagement->getCharacter();

        $this->assertGreaterThan(0, $character->gemBag->gemSlots->count());

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertEquals(0, GemBagSlot::whereHas('gemBag', function ($query) use ($character) {
            $query->where('character_id', $character->id);
        })->count());
    }

    public function test_alchemy_bag_slots_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
        ]);

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(AlchemyBagSlot::find($slot->id));
    }

    public function test_inventory_sets_and_slots_are_deleted(): void
    {
        $item = $this->createItem();

        $inventorySetManagement = $this->characterFactory
            ->inventorySetManagement()
            ->createInventorySets(1)
            ->putItemInSet($item, 0);

        $character = $inventorySetManagement->getCharacter();

        $this->assertGreaterThan(0, $character->inventorySets->count());

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertEquals(0, $character->refresh()->inventorySets()->count());
    }

    public function test_faction_loyalty_npcs_and_tasks_are_deleted(): void
    {
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->characterFactory->getCharacter(), 1, 1);

        $character = $factionLoyaltyFactory->getCharacter();
        $factionLoyaltyNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $taskId = $factionLoyaltyNpc->factionLoyaltyNpcTasks->id;

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(FactionLoyaltyNpc::find($factionLoyaltyNpc->id));
        $this->assertNull(FactionLoyaltyNpcTask::find($taskId));
    }

    public function test_global_event_crafting_inventories_are_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
        ]);

        $inventory = $this->createGlobalCraftingInventory([
            'character_id' => $character->id,
            'global_event_goal_id' => $globalEventGoal->id,
        ]);

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(GlobalEventCraftingInventory::find($inventory->id));
    }

    public function test_faction_loyalty_automations_and_logs_are_deleted(): void
    {
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->characterFactory->getCharacter(), 1, 1)
            ->createAutomation();

        $character = $factionLoyaltyFactory->getCharacter();
        $factionLoyaltyAutomation = $factionLoyaltyFactory->getFactionLoyaltyAutomation();
        $factionLoyaltyAutomationLog = $factionLoyaltyFactory->getFactionLoyaltyAutomationLog();

        $this->characterDeletion->deleteCharacterFromUser($character);

        $this->assertNull(FactionLoyaltyAutomation::find($factionLoyaltyAutomation->id));
        $this->assertNull($factionLoyaltyAutomationLog->fresh());
    }
}
