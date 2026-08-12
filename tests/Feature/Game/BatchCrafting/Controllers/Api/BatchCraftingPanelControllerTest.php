<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers\Api;

use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class BatchCraftingPanelControllerTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacter, CreateUser, RefreshDatabase;

    public function test_status_returns_an_inactive_contract_when_no_batch_is_visible(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertFalse($response->json('active'));
        $this->assertFalse($response->json('is_visible'));
        $this->assertNull($response->json('batch'));
    }

    public function test_status_returns_the_active_batch_contract(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory'],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->json('active'));
        $this->assertTrue($response->json('is_visible'));
        $this->assertSame(BatchCraftingType::CRAFT->value, $response->json('batch.batch_type'));
        $this->assertSame('Inventory', $response->json('batch.output_destination_label'));
    }

    public function test_dismiss_hides_a_completed_batch_panel(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        $dismissResponse = $this->actingAs($user)->call('POST', route('batch-crafting.dismiss', ['character' => $character]));

        $this->assertSame(200, $dismissResponse->getStatusCode(), $dismissResponse->getContent());

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertFalse($response->json('is_visible'));
    }
}
