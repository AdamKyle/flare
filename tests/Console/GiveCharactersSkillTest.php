<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class GiveCharactersSkillTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    public function testGiveCharacterSkill() {
        $gameSkill = $this->createGameSkill(['name' => 'Some Skill']);
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->assertEquals(0, $this->artisan('give:skills'));

        $character = $character->getCharacter();

        $this->assertNotEmpty($character->refresh()->skills()->where('game_skill_id', $gameSkill->id)->get());
    }

    public function testCharacterHasOneSkill() {
        $gameSkill = $this->createGameSkill(['name' => 'Some Skill']);
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $character->assignSkill($gameSkill, 20);

        $this->assertEquals(0, $this->artisan('give:skills'));

        $character = $character->getCharacter();

        $this->assertCount(1, $character->refresh()->skills()->where('game_skill_id', $gameSkill->id)->get());
    }

    public function testAssignClassSkillToCharacter() {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $gameSkill = $this->createGameSkill([
            'game_class_id' => $character->getCharacter()->class->id,
            'name'          => 'Apples'
        ]);

        $this->assertEquals(0, $this->artisan('give:skills'));

        $character = $character->getCharacter();

        $this->assertCount(1, $character->refresh()->skills()->where('game_skill_id', $gameSkill->id)->get());
    }

    public function testAssignNoSkillForNoGameSkills() {
        $this->assertEquals(0, $this->artisan('give:skills'));
    }
}
