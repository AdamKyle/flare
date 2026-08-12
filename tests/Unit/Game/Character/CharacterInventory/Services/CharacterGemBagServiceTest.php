<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class CharacterGemBagServiceTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterGemBagService $characterGemBagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();

        $this->characterGemBagService = resolve(CharacterGemBagService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterGemBagService = null;
    }

    public function test_get_character_gems()
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $result = $this->characterGemBagService->getGems($character);

        $this->assertEquals(200, $result['status']);
        $this->assertNotEmpty($result);
    }

    public function test_get_gem_data()
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $result = $this->characterGemBagService->getGemData($character, $character->gemBag->gemSlots->first());

        $this->assertEquals(200, $result['status']);
        $this->assertNotEmpty($result['gem']);
    }

    public function test_get_character_gems_filters_by_search_text()
    {
        $matchingGem = $this->createGem(['name' => 'Firestone']);
        $otherGem = $this->createGem(['name' => 'Icestone']);
        $character = $this->character
            ->gemBagManagement()->assignGemToBag($matchingGem->id)
            ->getCharacterFactory()
            ->gemBagManagement()->assignGemToBag($otherGem->id)
            ->getCharacter();

        $result = $this->characterGemBagService->getGems($character, searchText: 'Fire');

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_character_gems_filters_by_tier()
    {
        $tierOneGem = $this->createGem(['tier' => 1]);
        $tierTwoGem = $this->createGem(['tier' => 2]);
        $character = $this->character
            ->gemBagManagement()->assignGemToBag($tierOneGem->id)
            ->getCharacterFactory()
            ->gemBagManagement()->assignGemToBag($tierTwoGem->id)
            ->getCharacter();

        $result = $this->characterGemBagService->getGems($character, filters: ['tier' => 2]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function test_cannot_get_gem_data()
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $secondCharacter = (new CharacterFactory)->createBaseCharacter()->gemBagManagement()->assignGemsToBag()->getCharacter();

        $result = $this->characterGemBagService->getGemData($character, $secondCharacter->gemBag->gemSlots->first());

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No. Not yours!', $result['message']);
    }
}
