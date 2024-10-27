<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class TrinketCraftingControllerTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?Character $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Trinketry',
            'type' => SkillTypeValue::CRAFTING,
            'max_level' => 100,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill(
                $craftingSkill,
                10
            )
            ->getCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCraftableTrinkets()
    {
        $trinket = $this->createItem([
            'type' => 'trinket',
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/trinket-crafting/' . $this->character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['items'][0]['id'], $trinket->id);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
    }

    public function testCraftTrinket()
    {

        $trinket = $this->createItem([
            'type' => 'trinket',
            'skill_level_required' => 1,
            'skill_level_trivial' => 25
        ]);

        $this->character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/trinket-craft/' . $this->character->id, [
                'item_to_craft' => $trinket->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $this->assertNotEmpty($jsonData['items'][0]['id'], $trinket->id);
        $this->assertGreaterThan(0, $jsonData['skill_xp']['current_xp']);
    }
}
