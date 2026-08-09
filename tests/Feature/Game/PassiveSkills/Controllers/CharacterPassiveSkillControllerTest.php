<?php

namespace Tests\Feature\Game\PassiveSkills\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterPassiveSkillControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_view_another_characters_passive_skill(): void
    {
        $owner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $ownerSkill = $owner->passiveSkills()->first();

        $other = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($other->user)
            ->get(route('view.passive.skill', [
                'characterPassiveSkill' => $ownerSkill->id,
                'character' => $other->id,
            ]));

        $response->assertSessionHas('error', 'You do not own that.');
    }

    public function test_can_view_owned_passive_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $skill = $character->passiveSkills()->first();

        $response = $this->actingAs($character->user)
            ->get(route('view.passive.skill', [
                'characterPassiveSkill' => $skill->id,
                'character' => $character->id,
            ]));

        $response->assertOk();
        $response->assertViewIs('game.passive-skills.skill');
    }

    public function test_view_character_passive_skill_redirects_to_the_owned_passive_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $skill = $character->passiveSkills()->first();

        $response = $this->actingAs($character->user)
            ->get(route('view.character.passive.skill', [
                'passiveSkill' => $skill->passive_skill_id,
                'character' => $character->id,
            ]));

        $response->assertRedirect(route('view.passive.skill', [
            'characterPassiveSkill' => $skill->id,
            'character' => $character->id,
        ]));
    }
}
