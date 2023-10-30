<?php

namespace Tests\Unit\Game\CharacterInventory\Services;

use App\Game\CharacterInventory\Services\CharacterGemBagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterGemBagServiceTest extends TestCase {

    use RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterGemBagService $characterGemBagService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter();

        $this->characterGemBagService = resolve(CharacterGemBagService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->characterGemBagService = null;
    }

    public function testGetCharacterGems() {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $result = $this->characterGemBagService->getGems($character);

        $this->assertEquals(200, $result['status']);
        $this->assertNotEmpty($result['gem_slots']);
    }

    public function testGetGemData() {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $result = $this->characterGemBagService->getGemData($character, $character->gemBag->gemSlots->first()->id);

        $this->assertEquals(200, $result['status']);
        $this->assertNotEmpty($result['gem']);
    }

    public function testFailToGetGemData() {
        $character = $this->character->getCharacter();

        $result = $this->characterGemBagService->getGemData($character, rand(1000, 2000));

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No gem was found', $result['message']);
    }
}
