<?php

namespace Tests\Feature\Game\Automation\BatchCrafting\Controllers\Api;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class BatchCraftingControllerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
    }

    public function test_start_creates_a_running_batch_crafting_run(): void
    {
        Queue::fake([BatchCraftingJob::class]);
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNotNull($jsonData['batch_crafting_id']);
        $batchCrafting = BatchCrafting::find($jsonData['batch_crafting_id']);
        $this->assertSame($this->character->id, $batchCrafting->character_id);
        $this->assertSame('running', $batchCrafting->status);
        $this->assertNull($batchCrafting->ended_reason);
        $this->assertNull($batchCrafting->completed_at);
    }

    public function test_start_returns_a_validation_error_when_required_fields_are_missing(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', [
                '_token' => csrf_token(),
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_an_invalid_batch_type(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'not_real',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_an_invalid_disposition(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'not_real',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_an_invalid_craft_mode(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'not_real',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_an_invalid_item_id(): void
    {
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 0,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_a_zero_craft_amount(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 0,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_a_craft_amount_over_the_maximum(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 2001,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_for_a_decimal_craft_amount(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 1.5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_when_keep_is_missing_an_output_destination(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_when_sell_includes_an_output_destination(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'sell',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_start_returns_a_validation_error_when_destroy_includes_an_output_destination(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'destroy',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/start', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
    }

    public function test_preview_returns_the_lean_preview_payload(): void
    {
        $item = $this->createItem(['name' => 'Controller Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $payload = [
            '_token' => csrf_token(),
            'batch_type' => 'craft',
            'disposition' => 'keep',
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/preview', $payload);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($item->id, $jsonData['item']['id']);
        $this->assertSame(10, $jsonData['unit_cost']);
        $this->assertArrayNotHasKey('amount_preview', $jsonData);
    }

    public function test_cancel_stops_the_running_batch(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/cancel', [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertFalse(BatchCrafting::where('character_id', $this->character->id)->first()->isRunning());
    }

    public function test_status_reports_show_info_true_for_a_character_with_no_history(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/batch-crafting/'.$this->character->id.'/status');

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertTrue($jsonData['show_info']);
        $this->assertArrayNotHasKey('completed', $jsonData);
    }

    public function test_dismiss_clears_a_finished_batch_from_the_panel(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'completed_at' => now(),
            'ended_reason' => 'amount_reached',
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/dismiss', [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertNotNull(BatchCrafting::where('character_id', $this->character->id)->first()->panel_dismissed_at);
    }

    public function test_acknowledge_info_marks_the_introduction_as_seen(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/batch-crafting/'.$this->character->id.'/info/acknowledge', [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();

        $statusResponse = $this->actingAs($this->character->user)
            ->call('GET', '/api/batch-crafting/'.$this->character->id.'/status');

        $jsonData = json_decode($statusResponse->getContent(), true);
        $this->assertFalse($jsonData['show_info']);
    }
}
