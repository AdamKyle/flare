<?php

namespace Tests\Unit\Game\Maps\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Controllers\Api\MapController;
use App\Game\Maps\Requests\MoveRequest;
use App\Game\Maps\Requests\SetSailValidation;
use App\Game\Maps\Requests\TeleportRequest;
use App\Game\Maps\Requests\TraverseRequest;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Services\TeleportService;
use App\Game\Maps\Services\WalkingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MapControllerCooldownTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->getCharacter();

        $this->character->update([
            'can_move' => false,
        ]);
    }

    public function test_move_returns_unprocessable_when_character_cannot_move(): void
    {
        $response = $this->controller()->move(new MoveRequest, $this->character->refresh());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    public function test_traverse_returns_unprocessable_when_character_cannot_move(): void
    {
        $response = $this->controller()->traverse(
            new TraverseRequest,
            $this->character->refresh(),
            $this->createMock(MovementService::class)
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    public function test_teleport_returns_unprocessable_when_character_cannot_move(): void
    {
        $response = $this->controller()->teleport(new TeleportRequest, $this->character->refresh());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    public function test_set_sail_returns_unprocessable_when_character_cannot_move(): void
    {
        $response = $this->controller()->setSail(new SetSailValidation, $this->character->refresh());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    private function controller(): MapController
    {
        return new MapController(
            $this->createMock(MovementService::class),
            $this->createMock(TeleportService::class),
            $this->createMock(WalkingService::class),
            $this->createMock(SetSailService::class),
            $this->createMock(DistanceCalculation::class)
        );
    }
}
