<?php

namespace Tests\Unit\Game\Messages\Builder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Npc;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Battle\Values\CelestialConjureType;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class NpcServerMessageBuilderTest extends TestCase {

    use RefreshDatabase, CreateNpc, CreateCelestials, CreateMonster;

    private ?NpcServerMessageBuilder $npcServerMessageBuilder;

    private ?Npc $npc = null;

    public function setUp(): void {
        parent::setUp();

        $this->npcServerMessageBuilder = new NpcServerMessageBuilder();
        $this->npc                     = $this->createNpc();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->npcServerMessageBuilder = null;
        $this->npc                     = null;
    }

    public function testTookKingdom() {
        $message = $this->npcServerMessageBuilder->build('took_kingdom', $this->npc);

        $this->assertEquals($this->npc->real_name . ' smiles in your direction. "It\'s done!"', $message);
    }

    public function testKingdomTimeOut() {
        $message = $this->npcServerMessageBuilder->build('kingdom_time_out', $this->npc);

        $this->assertEquals($this->npc->real_name . ' looks disappointed as he looks at the ground and finally states: "No! You abandoned your last kingdom. You can wait..."', $message);
    }

    public function testCannotHave() {
        $message = $this->npcServerMessageBuilder->build('cannot_have', $this->npc);

        $this->assertEquals('"Sorry, you can\'t have that."', $message);
    }

    public function testTooPoor() {
        $message = $this->npcServerMessageBuilder->build('too_poor', $this->npc);

        $this->assertEquals('"I despise peasants! I spit on the ground before you! Come back when you can afford such treasures!"', $message);
    }

    public function testNotEnoughGold() {
        $message = $this->npcServerMessageBuilder->build('not_enough_gold', $this->npc);

        $this->assertEquals('"I do not like dealing with poor people. You do not have the gold, child!"', $message);
    }

    public function testConjure() {
        $message = $this->npcServerMessageBuilder->build('conjure', $this->npc);

        $this->assertEquals($this->npc->real_name . '\'s Eyes light up as magic races through the air. "It is done, child!" he bellows and magic strikes the earth!', $message);
    }

    public function testDead() {
        $message = $this->npcServerMessageBuilder->build('dead', $this->npc);

        $this->assertEquals('"I don\'t deal with dead people. Resurrect, child."', $message);
    }

    public function testPaidConjuringFee() {
        $message = $this->npcServerMessageBuilder->build('paid_conjuring', $this->npc);

        $this->assertEquals($this->npc->real_name . ' takes your currency and smiles: "Thank you, child. I shall begin the conjuration at once."', $message);
    }

    public function testAlreadyConjured() {
        $message = $this->npcServerMessageBuilder->build('already_conjured', $this->npc);

        $this->assertEquals('"No, child! I have already conjured for you!"', $message);
    }

    public function testMissingQuestItem() {
        $message = $this->npcServerMessageBuilder->build('missing_queen_item', $this->npc);

        $this->assertEquals($this->npc->real_name . ' looks at you with a blank stare. You try again and she just refuses to talk to you or acknowledge you. Maybe you need a quest item? Something to do with: Queens Decision (Quest)???', $message);
    }

    public function testPublicExists() {
        $message = $this->npcServerMessageBuilder->build('public_exists', $this->npc);

        $this->assertEquals('"No, child! Too many Celestial Entities wondering around can cause an unbalance, even The Creator can\'t fix!"', $message);
    }

    public function testLocationOfConjure() {
        $character      = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $celestialFight = $this->createCelestialFight([
            'monster_id'         => $this->createMonster()->id,
            'character_id'       => $character->id,
            'conjured_at'        => now(),
            'x_position'         => 0,
            'y_position'         => 0,
            'damaged_kingdom'    => false,
            'stole_treasury'     => false,
            'weakened_morale'    => false,
            'current_health'     => 1000,
            'max_health'         => 1000,
            'type'               => CelestialConjureType::PUBLIC,
        ]);

        $message = $this->npcServerMessageBuilder->build('location_of_conjure', $this->npc, $celestialFight);

        $this->assertEquals('"Child, I have conjured the portal, I have opened the gates! Here is the location (X/Y): '.$celestialFight->x_position.'/'.$celestialFight->y_position.' ('.$celestialFight->gameMapName().' Plane)"', $message);
    }

    public function testGivenItems() {
        $message = $this->npcServerMessageBuilder->build('given_item', $this->npc);

        $this->assertEquals('"Here child, take this! It might be of use to you!" (Check the help section under quest items to see what this does, or check your inventory and click on the item)', $message);
    }

    public function testCurrencyGiven() {
        $message = $this->npcServerMessageBuilder->build('currency_given', $this->npc);

        $this->assertEquals('"I have payment for you, here take this!"', $message);
    }

    public function testSkillUnlocked() {
        $message = $this->npcServerMessageBuilder->build('skill_unlocked', $this->npc);

        $this->assertEquals('"Child, I have done something magical! I have unlocked a skill for you!"', $message);
    }

    public function testCannotAffordConjuring() {
        $message = $this->npcServerMessageBuilder->build('cant_afford_conjuring', $this->npc);

        $this->assertEquals('"Why do these poor people always come to me?"
                ' . $this->npc->real_name . ' is not pleased with your lack of funds. try again when you can afford to be so brave.', $message);
    }

    public function testDefaultResponse() {
        $message = $this->npcServerMessageBuilder->build('hghfhdgd-dhgf', $this->npc);

        $this->assertEquals('', $message);
    }
}
