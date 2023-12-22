<?php

namespace Tests\Feature\Game\Gambler\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Gambler\Values\CurrencyValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GamblerControllerTest extends TestCase {

    use RefreshDatabase;

    private ?Character $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetSlots() {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/character/gambler');

        $jsonData = json_decode($response->getContent(), true);

        $icons = CurrencyValue::getIcons();

        $this->assertEquals($icons, $jsonData['icons']);
    }

    public function testRollSlots() {

        $this->character->update(['gold' => MaxCurrenciesValue::MAX_GOLD]);

        $this->character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/character/gambler/'.$this->character->id.'/slot-machine', [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $jsonData);
        $this->assertArrayHasKey('rolls', $jsonData);
    }
}
