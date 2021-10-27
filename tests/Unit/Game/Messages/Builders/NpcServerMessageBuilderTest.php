<?php

namespace Tests\Unit\Game\Messages\Builders;

use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class NpcServerMessageBuilderTest extends TestCase {
    use RefreshDatabase, CreateNpc, CreateCelestials, CreateMonster;

    private $npcMessageBuilder;

    private $npc;

    public function setUp(): void {
        parent::setUp();

        $this->npcMessageBuilder = resolve(NpcServerMessageBuilder::class);

        $this->npc = $this->createNpc();
    }

    public function testConjureMessage() {
        $message = $this->npcMessageBuilder->build('conjure', $this->npc);

        $this->assertEquals(
            $this->npc->real_name . '\'s Eyes light up as magic races through the air. "It is done child!" he bellows and magic strikes the earth!',
            $message
        );
    }

    public function testPaidForConjuration() {
        $message = $this->npcMessageBuilder->build('paid_conjuring', $this->npc);

        $this->assertEquals(
            $this->npc->real_name . ' takes your currency and smiles: "Thank you child. I shall begin the conjuration at once."',
            $message
        );
    }

    public function testAlreadyConjured() {
        $message = $this->npcMessageBuilder->build('already_conjured', $this->npc);

        $this->assertEquals(
            '"No child! I have already conjured for you!"',
            $message
        );
    }

    public function testPublicConjuredExists() {
        $message = $this->npcMessageBuilder->build('public_exists', $this->npc);

        $this->assertEquals(
            '"No Child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"',
            $message
        );
    }

    public function testPublicLocationOfConjure() {


        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter(false);

        $celestialFight = $this->createCelestialFight([
            'monster_id'      => $this->createMonster([
                'game_map_id' => $character->map->gameMap->id
            ])->id,
            'character_id'    => $character->id,
            'conjured_at'     => now(),
            'x_position'      => 16,
            'y_position'      => 36,
            'damaged_kingdom' => false,
            'stole_treasury'  => false,
            'weakened_morale' => false,
            'current_health'  => 1,
            'max_health'      => 1,
            'type'            => CelestialConjureType::PRIVATE,
        ]);

        $message = $this->npcMessageBuilder->build('location_of_conjure', $this->npc, $celestialFight);

        $this->assertEquals(
            '"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.' ('.$celestialFight->gameMapName().' Plane)"',
            $message
        );
    }

    public function testReturnEmptyString() {

        $message = $this->npcMessageBuilder->build('hgdjashdg', $this->npc);

        $this->assertEquals(
            '',
            $message
        );
    }
}
