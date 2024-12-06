<?php

namespace Tests\Unit\Game\Messages\Builder;

use App\Flare\Models\Npc;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\NpcMessageTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class NpcServerMessageBuilderTest extends TestCase
{
    use CreateCelestials, CreateMonster, CreateNpc, RefreshDatabase;

    private ?NpcServerMessageBuilder $npcServerMessageBuilder;

    private ?Npc $npc = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->npcServerMessageBuilder = new NpcServerMessageBuilder;
        $this->npc = $this->createNpc();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->npcServerMessageBuilder = null;
        $this->npc = null;
    }

    public function testPaidConjuringFee()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::PAID_CONJURING, $this->npc);

        $this->assertEquals($this->npc->real_name . ' takes your currency and smiles: "Thank you, child. I shall begin the conjuration at once."', $message);
    }

    public function testAlreadyConjured()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::ALREADY_CONJURED, $this->npc);

        $this->assertEquals('"No, child! I have already conjured for you!"', $message);
    }

    public function testPublicExists()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::PUBLIC_CONJURATION_EXISTS, $this->npc);

        $this->assertEquals('"No, child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"', $message);
    }

    public function testLocationOfConjure()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $celestialFight = $this->createCelestialFight([
            'monster_id' => $this->createMonster()->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => 0,
            'y_position' => 0,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::LOCATION_OF_CONJURE, $this->npc, $celestialFight);

        $this->assertEquals('"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): ' . $celestialFight->x_position . '/' . $celestialFight->y_position . ' (' . $celestialFight->gameMapName() . ' Plane)"', $message);
    }

    public function testGivenItems()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::GIVE_ITEM, $this->npc);

        $this->assertEquals('"Here child, take this! It might be of use to you!" (Check the help section under quest items to see what this does, or check your inventory and click on the item)', $message);
    }

    public function testCurrencyGiven()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::CURRENCY_GIVEN, $this->npc);

        $this->assertEquals('"I have payment for you, here take this!"', $message);
    }

    public function testSkillUnlocked()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::SKILL_UNLOCKED, $this->npc);

        $this->assertEquals('"Child, I have done something magical! I have unlocked a skill for you!"', $message);
    }

    public function testCannotAffordConjuring()
    {
        $message = $this->npcServerMessageBuilder->build(NpcMessageTypes::CANT_AFFORD_CONJURATION, $this->npc);

        $this->assertEquals('"Why do these poor people always come to me?"
                ' . $this->npc->real_name . ' is not pleased with your lack of funds. try again when you can afford to be so brave.', $message);
    }
}
