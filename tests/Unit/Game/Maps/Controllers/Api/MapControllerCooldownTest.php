<?php

namespace Tests\Unit\Game\Maps\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Maps\Controllers\Api\MapController;
use App\Game\Maps\Calculations\DistanceCalculation;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->getCharacter();

        $this->character->update([
            'can_move' => false,
        ]);
    }

    public function testMoveReturnsUnprocessableWhenCharacterCannotMove(): void
    {
        $response = $this->controller()->move(new MoveRequest, $this->character->refresh());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    public function testTraverseReturnsUnprocessableWhenCharacterCannotMove(): void
    {
        $response = $this->controller()->traverse(
            new TraverseRequest,
            $this->character->refresh(),
            $this->createMock(MovementService::class)
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    public function testTraverseDelegatesToArgumentServiceAndReturnsItsStatusAndBody(): void
    {
        $this->character->update(['can_move' => true]);
        $request = new TraverseRequest;
        $request->merge(['map_id' => 42]);
        $this->assertSame(['map_id' => 'required|integer'], $request->rules());
        $movementService = $this->createMock(MovementService::class);
        $movementService->expects($this->once())
            ->method('updateCharacterPlane')
            ->with(42, $this->character->refresh())
            ->willReturn(['status' => 201, 'can_access_hell_forged_shop' => true]);

        $response = $this->controller()->traverse($request, $this->character->refresh(), $movementService);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['can_access_hell_forged_shop' => true], $response->getData(true));
    }

    public function testTeleportReturnsUnprocessableWhenCharacterCannotMove(): void
    {
        $response = $this->controller()->teleport(new TeleportRequest, $this->character->refresh());

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['invalid input'], $response->getData(true));
    }

    public function testSetSailReturnsUnprocessableWhenCharacterCannotMove(): void
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
