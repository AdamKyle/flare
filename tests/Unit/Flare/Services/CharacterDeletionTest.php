<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameUnit;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Skill;
use App\Flare\Models\SmeltingProgress;
use App\Flare\Services\CharacterDeletion;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class CharacterDeletionTest extends TestCase
{
    use CreateCelestials,
        CreateDelveAutomation,
        CreateExplorationLog,
        CreateExplorationWarning,
        CreateGlobalEventGoal,
        CreateItem,
        CreateMonster,
        CreateNpc,
        CreateQuest,
        RefreshDatabase;

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
            'type' => NpcTypes::KINGDOM_HOLDER,
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

        ExplorationLog::factory()->create(['character_id' => $character->id]);
        ExplorationWarning::factory()->create(['character_id' => $character->id]);
        SmeltingProgress::factory()->create(['character_id' => $character->id]);

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
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
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
}
