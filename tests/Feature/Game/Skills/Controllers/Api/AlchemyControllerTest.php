<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class AlchemyControllerTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill, CreateItem, CreateGameSkill;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();
        $this->item      = $this->createItem([
            'type'                 => 'alchemy',
            'gold_dust_cost'       => 1000,
            'shards_cost'          => 1000,
            'skill_level_required' => 1,
            'crafting_type'        => 'alchemy',
            'can_craft'            => true,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill($this->createGameSkill(['type' => SkillTypeValue::ALCHEMY]))->updateCharacter([
            'gold_dust' => 10000,
            'shards'    => 10000,
        ]);
    }

    public function testGetAlchemyItems() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
                        ->json('GET', '/api/alchemy/' . $character->id)->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertCount(1, $content->items);
    }

    public function testAttemptToTransmute() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->json('POST', '/api/transmute/' . $character->id, [
                'item_to_craft' => $this->item->id
            ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertCount(1, $content->items);
    }
}
