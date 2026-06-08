<?php

namespace Tests\Feature\Game\PassiveSkills\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterPassiveSkillWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCannotViewAnotherCharactersPassiveSkill(): void
    {
        $owner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $ownerSkill = $owner->passiveSkills()->first();

        $other = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->actingAs($other->user)
            ->get(route('view.passive.skill', [
                'characterPassiveSkill' => $ownerSkill->id,
                'character' => $other->id,
            ]));

        $this->assertSessionHas('error', 'You do not own that.');
    }

}
