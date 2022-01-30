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

    public function testTookKingdomMessage() {
        $message = $this->npcMessageBuilder->build('took_kingdom', $this->npc);

        $this->assertEquals(
            $this->npc->real_name . ' smiles in your direction. "It\'s done!"',
            $message
        );
    }

    public function testCannotTakeKingdomMessage() {
        $message = $this->npcMessageBuilder->build('cannot_have', $this->npc);

        $this->assertEquals(
            '"Sorry, you can\'t have that."',
            $message
        );
    }

    public function testTestTooPoorMessage() {
        $message = $this->npcMessageBuilder->build('too_poor', $this->npc);

        $this->assertEquals(
            '"I despise peasants! I spit on the ground before you! Come back when you can afford such treasures!"',
            $message
        );
    }

    public function testNotEnoughGold() {
        $message = $this->npcMessageBuilder->build('not_enough_gold', $this->npc);

        $this->assertEquals(
            '"I do not like dealing with poor people. You do not have the gold child!"',
            $message
        );
    }

    public function testMissingQueenItem() {
        $message = $this->npcMessageBuilder->build('missing_queen_item', $this->npc);

        $this->assertEquals(
            $this->npc->real_name . ' looks at you with a blank stare. You try again and she just refuses to talk to you or acknowledge you. Maybe you need a quest item? Something to do with: Queens Decision (Quest) ???',
            $message
        );
    }

    public function testNoSkill() {
        $message = $this->npcMessageBuilder->build('no_skill', $this->npc);

        $this->assertEquals(
            '"Sorry child, I do not see a skill that needs unlocking."',
            $message
        );
    }

    public function testDontOwnSkillSkill() {
        $message = $this->npcMessageBuilder->build('dont_own_skill', $this->npc);

        $this->assertEquals(
            '"Sorry child, you don\'t seem to own the skill to be unlocked!" (Chances are if you are seeing this, it\'s a bug. Head to discord post in the bugs section, link at the top)',
            $message
        );
    }

    public function testMissingQueenPlane() {
        $message = $this->npcMessageBuilder->build('queen_plane', $this->npc);

        $this->assertEquals(
            $this->npc->real_name . ' looks at you, blinks here eyes and screams: "NO! NO! NO! you have to come to me child. Come to me and let me love you...Oooooh hooo hoo hoo!" You must be any where in Hell to access her.',
            $message
        );
    }

    public function testXpGiven() {
        $message = $this->npcMessageBuilder->build('xp_given', $this->npc);

        $this->assertEquals(
            '"Here child, take this for your hard work!"',
            $message
        );
    }

    public function testWhatDoYouWant() {
        $message = $this->npcMessageBuilder->build('what_do_you_want', $this->npc);

        $this->assertEquals(
            '"Select something child, one of those green items and tell me what you want. Remember I am not a cheap woman. You must please me to get what you want! I am the Queen of Hearts after all. Oooooh hooo hoo hoo!"',
            $message
        );
    }

    public function testMissingParentQuest() {
        $message = $this->npcMessageBuilder->build('missing_parent_quest', $this->npc);

        $this->assertEquals(
            '"Child! there is something you have to do, before you talk to me. Go do it!" (Open Plane Quests and find the quest you are trying to complete. Quests with lines connecting must be done in order).',
            $message
        );
    }

    public function testTakeALook() {
        $message = $this->npcMessageBuilder->build('take_a_look', $this->npc);

        $this->assertEquals(
            '"Why don\'t you take a look, and show me what you can afford my child."',
            $message
        );
    }

    public function testKingdomTimeoutMessage() {
        $message = $this->npcMessageBuilder->build('kingdom_time_out', $this->npc);

        $this->assertEquals(
            $this->npc->real_name . ' looks disappointed as he looks at the ground and finally states: "No! You abandoned your last kingdom. You can wait...."',
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
