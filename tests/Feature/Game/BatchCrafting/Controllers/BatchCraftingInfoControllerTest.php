<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

use App\Flare\Models\BatchCrafting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class BatchCraftingInfoControllerTest extends TestCase
{
    use CreateCharacter, CreateUser, RefreshDatabase;

    public function testInfoAcknowledgementStoresState(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.info.acknowledge', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->first());
    }
}
