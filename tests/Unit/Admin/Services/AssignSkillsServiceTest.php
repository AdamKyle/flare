<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\AssignSkillService;
use App\Admin\Services\ItemAffixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use Exception;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateUser;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignSkillsServiceTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateGameSkill;

    private $character;

    private $item;

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
            $this->assertEquals('Monster not found for id: 1', $e->getMessage());
        }
    }

    protected function baseSetUp() {
        $user = $this->createUser();

        $this->character = (new CharacterSetup)->setupCharacter($user)
                                               ->getCharacter();
    }
}
