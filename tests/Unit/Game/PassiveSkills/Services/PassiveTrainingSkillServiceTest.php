<?php

namespace Tests\Unit\Game\PassiveSkills\Services;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Game\PassiveSkills\Services\PassiveSkillTrainingService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class PassiveTrainingSkillServiceTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?PassiveSkillTrainingService $passiveSkillTrainingService;

    public function setUp(): void {
        parent::setUp();

        $this->character                   = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->passiveSkillTrainingService = resolve(PassiveSkillTrainingService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character                   = null;
        $this->passiveSkillTrainingService = null;
    }

    public function testTrainAPassive() {
       Queue::fake();

       Event::fake();

       $character = $this->character->getCharacter();

       $this->passiveSkillTrainingService->trainSkill($character->passiveSkills()->first(), $character);

       Queue::assertPushed(TrainPassiveSkill::class);
       Event::assertDispatched(UpdateTopBarEvent::class);
    }
}
