<?php

namespace Tests\Feature\Game\Gambler\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Gambler\Values\CurrencyValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GamblerControllerTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_slots_returns_symbols_and_spin_status()
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/character/gambler');

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertEquals(CurrencyValue::getIcons(), $jsonData['icons']);
        $this->assertTrue($jsonData['can_spin']);
        $this->assertSame(0, $jsonData['timeout_for']);
    }

    public function test_get_slots_returns_remaining_cooldown_for_the_authenticated_character()
    {
        $this->character->update([
            'can_spin' => false,
            'can_spin_again_at' => now()->addSeconds(9),
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/character/gambler');

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertFalse($jsonData['can_spin']);
        $this->assertGreaterThan(0, $jsonData['timeout_for']);
    }

    public function test_character_in_cooldown_cannot_post_another_spin()
    {
        $this->character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'can_spin' => false,
            'can_spin_again_at' => now()->addSeconds(9),
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/character/gambler/'.$this->character->id.'/slot-machine');

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You must wait for the slot machine to cool down before spinning again.']);
    }

    public function test_roll_slots()
    {

        $this->character->update(['gold' => CurrencyLimit::MAX_GOLD]);

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
