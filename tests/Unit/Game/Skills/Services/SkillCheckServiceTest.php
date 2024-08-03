<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class SkillCheckServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?SkillCheckService $skillCheckService;

    private ?GameSkill $gemSkill;

    public function setUp(): void
    {
        parent::setUp();

        $this->gemSkill = $this->createGameSkill([
            'name' => 'Gem Crafting',
            'type' => SkillTypeValue::GEM_CRAFTING,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->gemSkill, 200
        )->givePlayerLocation();

        $this->skillCheckService = resolve(SkillCheckService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->gemSkill = null;
        $this->skillCheckService = null;
    }

    public function testReturnMaxRollForCharacterRollWhenSkillIsMaxed()
    {

        $skill = $this->character->getCharacter()->skills->where('game_skill_id', $this->gemSkill->id)->first();

        $result = $this->skillCheckService->characterRoll($skill);

        $this->assertEquals(401, $result);
    }
}
