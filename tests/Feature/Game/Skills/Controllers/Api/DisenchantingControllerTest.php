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
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateNpc;

class DisenchantingControllerTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameSkill, CreateItem, CreateItemAffix, CreateNpc, RefreshDatabase;

    private ?Character $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING,
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

    public function testDisenchantItem()
    {
        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
            'gold_dust_cost' => 100,
            'shards_cost' => 50,
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix'
            ]),
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix'
            ]),
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/disenchant/' . $item->id);

        $jsonData = json_decode($response->getContent(), true);

        $character = $this->character->refresh();

        $this->assertGreaterThan(0, $character->gold_dust);
        $this->assertEquals('Disenchanted item ' . $item->affix_name . ' Check server message tab for Gold Dust output.', $jsonData['message']);
        $this->assertEmpty($jsonData['inventory']['inventory']);
    }
}
