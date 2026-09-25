<?php

namespace Tests\Feature\Admin\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminMessagesControllerTest extends TestCase
{
    use CreateMessage, CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_chat_history_returns_public_messages_from_the_previous_thirty_days(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createMessage($character->user, [
            'message' => 'Recent public message',
            'created_at' => now()->subDays(10),
        ]);

        $this->createMessage($character->user, [
            'message' => 'Old public message',
            'created_at' => now()->subDays(31),
        ]);

        $this->createMessage($character->user, [
            'message' => 'Private message',
            'from_user' => $character->user->id,
            'to_user' => $admin->id,
            'created_at' => now()->subDay(),
        ]);

        $this->createMessage($admin, [
            'message' => 'Creator message',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/chat-messages');

        $chatMessages = collect($response->json('chat_messages'));

        $response->assertOk();
        $this->assertSame(['Creator message', 'Recent public message'], $chatMessages->pluck('message')->all());
        $this->assertSame('The Creator', $chatMessages->firstWhere('message', 'Creator message')['name']);
        $this->assertSame($character->name, $chatMessages->firstWhere('message', 'Recent public message')['name']);
    }
}
