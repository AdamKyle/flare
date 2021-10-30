<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Skill;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class CharacterSkillControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testShouldSeeSkillPage() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visit(route('skill.character.info', [
            'skill' => $this->fetchSkill('Dodge')->id,
        ]))->see('Dodge');
    }

    public function testNotShouldSeeSkillPage() {
        $this->visit(route('skill.character.info', [
            'skill' => $this->fetchSkill('Dodge')->id,
        ]))->dontSee('Dodge');
    }

    protected function fetchSkill(string $name): Skill {
        return $this->character->getCharacter(false)->skills->where('name', $name)->first();
    }
}
