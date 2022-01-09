<?php

namespace Tests\Feature\Game\Passives;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterPassiveSkillControllerTest extends TestCase
{
    use RefreshDatabase;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter(false);
    }

    public function testCanViewYourPassiveSkill() {
        $this->actingAs($this->character->user)
             ->visitRoute('game')
             ->visitRoute('view.character.passive.skill', [
                 'passiveSkill' => $this->character->passiveSkills()->first()->passiveSkill->id,
                 'character'    => $this->character->id,
             ])->see($this->character->passiveSkills()->first()->name);
    }
}