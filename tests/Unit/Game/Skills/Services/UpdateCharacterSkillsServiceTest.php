<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Game\Skills\Events\UpdateCharacterSkills;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class UpdateCharacterSkillsServiceTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?UpdateCharacterSkillsService $updateCharacterSkills;

    private ?GameSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skill = $this->createGameSkill([
            'name' => 'skill',
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => true,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->skill
        )->givePlayerLocation();

        $this->updateCharacterSkills = resolve(UpdateCharacterSkillsService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->skill = null;
        $this->updateCharacterSkills = null;
    }

    public function test_update_character_training_skills()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->updateCharacterSkills->updateCharacterSkills($character);

        Event::assertDispatched(UpdateCharacterSkills::class);
    }

    public function test_update_charactercrafting_skills()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->updateCharacterSkills->updateCharacterCraftingSkills($character);

        Event::assertDispatched(UpdateCharacterSkills::class);
    }
}
