<?php

namespace Tests\Unit\Game\Messages\Handlers;



use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Game\Messages\Handlers\NpcKingdomHandler;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateKingdomBuilding;
use Tests\Traits\CreateNpc;

class NpcKingdomHandlerTest extends TestCase {
    use RefreshDatabase, CreateNpc, CreateKingdom, CreateGameBuilding, CreateKingdomBuilding;

    private $character;

    private $npcKingdomHandler;

    private $npc;

    public function setUp(): void {
        parent::setUp();

        $this->character         = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->npc               = $this->createNpc([ 'game_map_id' => GameMap::first()->id ]);

        $this->npcKingdomHandler = resolve(NpcKingdomHandler::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character         = null;

        $this->npcKingdomHandler = null;
    }

    public function testCharacterCannotSettleKingdomViaNPC() {
        $this->character->updateCharacter([
            'can_settle_again_at' => now()->addMinutes(40),
        ]);

        $this->createNpcKingdom();

        $character = $this->character->getCharacter(false);


        $this->npcKingdomHandler->takeKingdom($character, $this->npc);

        $character = $character->refresh();

        $this->assertEmpty($character->kingdoms);

    }

    public function testCharacterCanHaveKingdom() {
        $this->character->updateCharacter([
            'gold' => 100000,
        ]);

        $this->createNpcKingdom();

        $character = $this->character->getCharacter(false);

        $this->npcKingdomHandler->takeKingdom($character, $this->npc);

        $character = $character->refresh();

        $this->assertNotEmpty($character->kingdoms);
    }

    public function testCharacterCanHaveKingdomBuildingsAreLocked() {
        $this->character->updateCharacter([
            'gold' => 100000,
        ])->createPassiveForCharacter(PassiveSkillTypeValue::UNLOCKS_BUILDING, [
            'name'          => 'Goblin Bank',
            'is_locked'     => true,
        ]);


        $kingdom = $this->createNpcKingdom();

        $gameBuilding = $this->createGameBuilding(['name' => 'Goblin Bank']);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
            'is_locked'          => false,
        ]);

        $character = $this->character->getCharacter(false);

        $this->npcKingdomHandler->takeKingdom($character, $this->npc);

        $character = $character->refresh();

        $this->assertNotEmpty($character->kingdoms);

        $kingdom = $character->kingdoms->first();

        $this->assertNotEmpty($kingdom->buildings()->where('is_locked', true)->get());
    }

    public function testCharacterCanHaveKingdomBuildingsAreUnlocked() {
        $this->character->updateCharacter([
            'gold' => 100000,
        ])->createPassiveForCharacter(PassiveSkillTypeValue::UNLOCKS_BUILDING, [
            'name'          => 'Goblin Bank',
            'is_locked'     => false,
            'current_level'  => 1
        ]);


        $kingdom = $this->createNpcKingdom();

        $gameBuilding = $this->createGameBuilding(['name' => 'Goblin Bank']);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
            'is_locked'          => true,
        ]);

        $character = $this->character->getCharacter(false);

        $this->npcKingdomHandler->takeKingdom($character, $this->npc);

        $character = $character->refresh();

        $this->assertNotEmpty($character->kingdoms);

        $kingdom = $character->kingdoms->first();

        $this->assertEmpty($kingdom->buildings()->where('is_locked', true)->get());
    }

    public function testCharacterCannotAffordToHaveKingdom() {
        $this->character->updateCharacter([
            'gold' => 0,
        ])->createPassiveForCharacter(PassiveSkillTypeValue::UNLOCKS_BUILDING, [
            'name'          => 'Goblin Bank',
            'is_locked'     => false,
            'current_level'  => 1
        ])->kingdomManagement()
          ->assignKingdom()
          ->assignBuilding()
          ->assignUnits()
          ->getCharacterFactory();

        $kingdom = $this->createNpcKingdom();

        $gameBuilding = $this->createGameBuilding(['name' => 'Goblin Bank']);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
            'is_locked'          => true,
        ]);

        $character = $this->character->getCharacter(false);

        $this->npcKingdomHandler->takeKingdom($character, $this->npc);

        $character = $character->refresh();

        $this->assertCount(1, $character->kingdoms);
    }

    protected function createNpcKingdom(): Kingdom {
        return $this->createKingdom([
            'character_id' => null,
            'npc_owned'    => true,
            'game_map_id'  => GameMap::first()->id,
        ]);
    }
}
