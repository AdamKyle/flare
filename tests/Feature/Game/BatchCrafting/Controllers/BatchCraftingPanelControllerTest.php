<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class BatchCraftingPanelControllerTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacter, CreateUser, RefreshDatabase;

    public function testCompletedPanelRemainsUntilDismissed(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $this->actingAs($user)->get(route('batch-crafting.status', ['character' => $character]));

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->whereNull('panel_dismissed_at')->first()->panel_dismissed_at);
    }

    public function testDismissedPanelNoLongerReturns(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $this->actingAs($user)->post(route('batch-crafting.dismiss', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('panel_dismissed_at')->first());
    }
}
