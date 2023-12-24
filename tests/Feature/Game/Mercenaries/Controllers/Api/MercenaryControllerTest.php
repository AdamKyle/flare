<?php

namespace Tests\Feature\Game\Mercenaries\Controllers\Api;

use App\Flare\Models\CharacterMercenary;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Mercenaries\Values\ExperienceBuffValue;
use App\Game\Mercenaries\Values\MercenaryValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MercenaryControllerTest extends TestCase {

    use RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testLst() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/mercenaries/list/' . $character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['merc_data']);
        $this->assertNotEmpty($jsonData['merc_xp_buffs']);
        $this->assertNotEmpty($jsonData['mercs_to_buy']);
    }

    public function testBuy() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10000000
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/mercenaries/buy/' . $character->id, [
                '_token' => csrf_token(),
                'type' => MercenaryValue::CHILD_OF_GOLD_DUST,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $this->assertNotEmpty($jsonData['mercs_to_buy']);
        $this->assertNotEmpty($jsonData['merc_data']);
        $this->assertEquals('You purchased: ' . $mercType->getName() . '!', $jsonData['message']);
    }

    public function testPurchaseBuff() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $character->update(['gold' => MaxCurrenciesValue::MAX_GOLD]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/mercenaries/purcahse-buff/' . $character->id . '/' . $merc->id, [
                '_token' => csrf_token(),
                'type' => ExperienceBuffValue::RANK_SIX
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Applied the buff to the Mercenary', $jsonData['message']);
    }

    public function testReincarnate() {
        $character = $this->character->getCharacter();

        $mercType = new MercenaryValue(MercenaryValue::CHILD_OF_GOLD_DUST);

        $merc = CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => MercenaryValue::CHILD_OF_GOLD_DUST,
            'current_level'        => 100,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
            'times_reincarnated'   => 0
        ]);

        $character->update(['shards' => 1000]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/mercenaries/re-incarnate/' . $character->id . '/' . $merc->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Re-incarnated Mercenary!', $jsonData['message']);
        $this->assertCount(1, $jsonData['merc_data']);
        $this->assertEquals(1, $jsonData['merc_data'][0]['times_reincarnated']);
    }
}
