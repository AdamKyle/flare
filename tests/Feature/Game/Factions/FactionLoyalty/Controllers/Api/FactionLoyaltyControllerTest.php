<?php

namespace Tests\Feature\Game\Factions\FactionLoyalty\Controllers\Api;

use App\Game\Automation\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class FactionLoyaltyControllerTest extends TestCase
{
    use CreateCharacterAutomation, RefreshDatabase;

    public function test_fetch_loyalty_info_returns_success(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/faction-loyalty/'.$character->id);

        $response->assertOk();
    }

    public function test_pledge_loyalty_is_blocked_by_exploration_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $faction = $factionLoyaltyFactory->getFactions()[0];

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/pledge/'.$character->id.'/'.$faction->id);

        $response->assertStatus(422);
    }

    public function test_pledge_loyalty_succeeds_when_faction_is_maxed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $pledgedFactionLoyalty = $factionLoyaltyFactory->getPledgedFactionLoyalty();
        $faction = $pledgedFactionLoyalty->faction;

        $pledgedFactionLoyalty->update(['is_pledged' => false]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/pledge/'.$character->id.'/'.$faction->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('Pledged to:', $jsonData['message']);
    }

    public function test_remove_pledge_is_blocked_by_exploration_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $faction = $factionLoyaltyFactory->getPledgedFactionLoyalty()->faction;

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/remove-pledge/'.$character->id.'/'.$faction->id);

        $response->assertStatus(422);
    }

    public function test_remove_pledge_succeeds_for_pledged_faction(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $faction = $factionLoyaltyFactory->getPledgedFactionLoyalty()->faction;

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/remove-pledge/'.$character->id.'/'.$faction->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('No longer pledged to:', $jsonData['message']);
    }

    public function test_assist_npc_is_blocked_by_exploration_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $npc = $factionLoyaltyFactory->getFactionLoyaltyNpcs()[1];

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/assist/'.$character->id.'/'.$npc->id);

        $response->assertStatus(422);
    }

    public function test_assist_npc_succeeds_for_owned_npc(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $npc = $factionLoyaltyFactory->getFactionLoyaltyNpcs()[1];

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/assist/'.$character->id.'/'.$npc->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringContainsString('You are now assisting', $jsonData['message']);
    }

    public function test_stop_assisting_npc_is_blocked_by_exploration_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $npc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/stop-assisting/'.$character->id.'/'.$npc->id);

        $response->assertStatus(422);
    }

    public function test_stop_assisting_npc_succeeds_for_owned_npc(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $npc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $response = $this->actingAs($character->user)
            ->postJson('/api/faction-loyalty/stop-assisting/'.$character->id.'/'.$npc->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringContainsString('You stopped assisting', $jsonData['message']);
    }
}
