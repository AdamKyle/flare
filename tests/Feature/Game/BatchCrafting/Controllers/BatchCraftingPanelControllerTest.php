<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

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

        $response = $this->actingAs($user)->get(route('batch-crafting.status', ['character' => $character]));

        $response->assertOk();
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

        $response = $this->actingAs($user)->get(route('batch-crafting.status', ['character' => $character]));

        $response->assertOk();
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

        $this->actingAs($user)
            ->post(route('batch-crafting.dismiss', ['character' => $character]))
            ->assertOk();

        $response = $this->actingAs($user)->get(route('batch-crafting.status', ['character' => $character]));

        $this->assertFalse($response->json('is_visible'));
    }
}
