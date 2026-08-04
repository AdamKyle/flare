<?php

namespace Tests\Feature\Game\Npcs\Actions\Seer\Controllers\Api;

use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class SeerCampControllerTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_visiting_seer_camp_returns_the_authoritative_costs()
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/visit-seer-camp/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals([
            'socket' => 2000,
            'attach' => 500,
            'replace' => 10,
            'remove_one' => 10,
        ], $jsonData['costs']);
    }

    public function test_fetching_removal_data_returns_costs_calculated_from_actual_attached_gem_count()
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 2,
        ]);

        $firstGem = $this->createGem();
        $secondGem = $this->createGem();

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $firstGem->id,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $secondGem->id,
        ]);

        $item = $item->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/gems-to-remove/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(10, $jsonData['gems'][0]['remove_one_cost']);
        $this->assertEquals(20, $jsonData['gems'][0]['remove_all_cost']);
    }

    public function test_one_successful_mutation_response_retains_items_gems_costs_and_message()
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(50);
            })
        );

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 0,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/add-sockets/'.$character->id, [
                'slot_id' => $slot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('items', $jsonData);
        $this->assertArrayHasKey('gems', $jsonData);
        $this->assertArrayHasKey('costs', $jsonData);
        $this->assertEquals('Attached sockets to item! (Old Socket Count: 0, New Count: 2).', $jsonData['message']);
    }

    public function test_expected_validation_failure_does_not_change_gold_bars_or_item_state()
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 0,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $goldBarsBefore = $character->kingdoms->sum('gold_bars');

        $this->actingAs($character->user)
            ->json('POST', '/api/seer-camp/add-sockets/'.$character->id, []);

        $response = $this->response;

        $response->assertStatus(422);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Error. Invalid Input.', $jsonData['errors']['slot_id'][0]);
        $this->assertEquals($goldBarsBefore, $character->refresh()->kingdoms->sum('gold_bars'));
        $this->assertEquals(0, $item->refresh()->socket_count);
    }
}
