<?php

namespace Tests\Unit\Admin\Services;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Services\AssignSkillService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignSkillsServiceTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    private $character;

    public function setUp(): void {
        parent::setup();

        $this->baseSetUp();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testExceptionMessageForUnknownType() {
        $service = resolve(AssignSkillService::class);

        try {
            $service->assignSkill('test', $this->createGameSkill());
        } catch (Exception $e) {
            $this->assertEquals('Could not determine who to assign skill to. $for: test', $e->getMessage());
        }
    }

    public function testExceptionMessageForUnknownMonster() {
        $service = resolve(AssignSkillService::class);

        try {
            $service->assignSkill('select-monster', $this->createGameSkill(), 1);
        } catch (Exception $e) {
            $this->assertEquals('Could not determine who to assign skill to. $for: select-monster', $e->getMessage());
        }
    }

    protected function baseSetUp() {

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->getCharacter();
    }
}
