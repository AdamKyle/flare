<?php

namespace Tests\Unit\Game\PassiveSkills\Services;

use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use App\Game\PassiveSkills\Services\PassiveSkillTrainingService;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class PassiveSkillTrainingServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?PassiveSkillTrainingService $passiveSkillTrainingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->passiveSkillTrainingService = resolve(PassiveSkillTrainingService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->passiveSkillTrainingService = null;
    }

    public function test_train_a_passive()
    {
        Queue::fake();

        Event::fake();

        $character = $this->character->getCharacter();

        $this->passiveSkillTrainingService->trainSkill($character->passiveSkills()->first(), $character);

        Queue::assertPushed(TrainPassiveSkill::class);
        Event::assertDispatched(UpdateCharacterBaseDetailsEvent::class);
    }

    public function test_train_skill_returns_false_and_finalizes_when_already_maxed()
    {
        Queue::fake();

        Event::fake();

        $characterFactory = $this->character->passiveSkillManagement()->assignPassiveSkill(
            PassiveSkillTypeValue::KINGDOM_DEFENCE,
            1,
            ['max_level' => 1],
        )->getCharacterFactory();

        $character = $characterFactory->getCharacter();
        $passive = $character->passiveSkills()->latest('id')->first();

        $result = $this->passiveSkillTrainingService->trainSkill($passive, $character);

        $this->assertFalse($result);
        $this->assertNull($passive->refresh()->started_at);
        Queue::assertNotPushed(TrainPassiveSkill::class);
    }
}
