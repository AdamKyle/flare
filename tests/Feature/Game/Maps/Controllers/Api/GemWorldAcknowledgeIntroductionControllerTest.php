<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use App\Game\Automation\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class GemWorldAcknowledgeIntroductionControllerTest extends TestCase
{
    use CreateCharacterAutomation, RefreshDatabase;

    public function test_acknowledgement_persists_the_timestamp_once(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertNull($character->gem_world_introduction_acknowledged_at);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/gem-world/acknowledge-introduction/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($jsonData['gem_world_introduction_acknowledged_at']);
        $this->assertNotNull($character->fresh()->gem_world_introduction_acknowledged_at);
    }

    public function test_acknowledgement_is_not_blocked_while_the_character_is_exploring(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/gem-world/acknowledge-introduction/'.$character->id);

        $response->assertStatus(200);
    }

    public function test_a_different_authenticated_user_cannot_acknowledge_for_another_characters_owner(): void
    {
        $owner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($otherCharacter->user)
            ->call('POST', '/api/map/gem-world/acknowledge-introduction/'.$owner->id);

        $response->assertRedirect();
        $this->assertNull($owner->fresh()->gem_world_introduction_acknowledged_at);
    }

    public function test_an_unauthenticated_request_is_rejected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->call('POST', '/api/map/gem-world/acknowledge-introduction/'.$character->id);

        $response->assertRedirect();
        $this->assertNull($character->fresh()->gem_world_introduction_acknowledged_at);
    }
}
