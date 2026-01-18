<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DisenchantingControllerTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING->value,
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

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_disenchant_item()
    {
        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
            'gold_dust_cost' => 100,
            'shards_cost' => 50,
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ]),
        ]);

        $slot = $this->character->inventory->slots()->create([
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
            ->call('POST', '/api/disenchant/'.$slot->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $character = $this->character->refresh();

        $this->assertGreaterThan(0, $character->gold_dust);
        $this->assertEquals('Disenchanted item '.$item->affix_name.' Check server message tab for Gold Dust output.', $jsonData['message']);
    }
}
