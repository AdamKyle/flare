<?php

namespace Tests\Unit\Flare\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class UpdateCharacterSkillsTest extends TestCase
{

    use RefreshDatabase,
        CreateUser;

    public function testUpdateCharacterSkills() {
        $character = (new CharacterSetup)->setupCharacter($this->createUser())->getCharacter();

        $this->assertTrue($character->skills->isEmpty());

        $this->artisan('update:character:skills');

        $this->assertFalse($character->refresh()->skills->isEmpty());
    }

}
