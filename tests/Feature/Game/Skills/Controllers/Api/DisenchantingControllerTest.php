<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Values\SkillTypeValue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DisenchantingControllerTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill, CreateItem, CreateItemAffix;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();
        $this->item      = $this->createItem([
            'item_suffix_id' => $this->createItemAffix()->id
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->assignSkill($this->createGameSkill([
                                                     'type' => SkillTypeValue::DISENCHANTING,
                                                 ]))
                                                 ->inventoryManagement()
                                                 ->giveItem($this->item);
    }


    public function testCanDisenchantItem() {
        $character = $this->character->getCharacter();

        $disenchantingService = Mockery::mock(DisenchantService::class)->makePartial();

        $this->app->instance(DisenchantService::class, $disenchantingService);

        $disenchantingService->shouldReceive('characterRoll')->once()->andReturn(1000);

        $response = $this->actingAs($character->user)->json('POST', '/api/disenchant/' . $this->item->id)->response;

        $this->assertEquals(200, $response->status());

        $foundItem = $character->refresh()->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($foundItem);
    }

    public function testCannotDisenchantItem() {
        $character = $this->character->getCharacter();

        // Delete it first:
        $this->actingAs($character->user)->json('POST', '/api/disenchant/' . $this->item->id);

        $response = $this->actingAs($character->user)->json('POST', '/api/disenchant/' . $this->item->id)->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCanDestroyItem() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)->json('POST', '/api/destroy/' . $this->item->id)->response;

        $this->assertEquals(200, $response->status());

        $foundItem = $character->refresh()->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($foundItem);
    }

    public function testCannotDestroyItem() {
        $character = $this->character->getCharacter();

        // Delete it first:
        $this->actingAs($character->user)->json('POST', '/api/destroy/' . $this->item->id);

        $response = $this->actingAs($character->user)->json('POST', '/api/destroy/' . $this->item->id)->response;

        $this->assertEquals(200, $response->status());
    }
}
