<?php

namespace Tests\Feature\Game\Core\Controllers;

use App\Flare\Models\QuestsCompleted;
use App\Game\Character\Values\NameTag;
use App\Game\Core\Values\FeatureType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class SettingsControllerSecurityTest extends TestCase
{
    use CreateItem, CreateNpc, CreateQuest, RefreshDatabase;

    public function test_cosmetic_text_updates_only_validated_cosmetic_fields(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
            'item_id' => $this->createItem()->id,
            'unlocks_feature' => FeatureType::COSMETIC_TEXT->value,
            'unlocks_skill' => false,
        ]);
        QuestsCompleted::create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);
        $user = $character->user;
        $originalEmail = $user->email;
        $originalPassword = $user->password;

        $response = $this->actingAs($user)->post(route('user.settings.cosmetic-text', [
            'user' => $user->id,
        ]), [
            'chat_text_color' => 'ocean-depths',
            'chat_is_bold' => true,
            'chat_is_italic' => true,
            'email' => 'attacker@example.com',
            'password' => 'compromised',
            'is_banned' => true,
            'is_silenced' => true,
            'timeout_until' => now()->addYear(),
            'will_be_deleted' => true,
        ])->response;

        $response->assertSessionHas('success', 'Updated Cosmetic Text options');
        $user = $user->refresh();
        $this->assertSame('ocean-depths', $user->chat_text_color);
        $this->assertTrue($user->chat_is_bold);
        $this->assertTrue($user->chat_is_italic);
        $this->assertSame($originalEmail, $user->email);
        $this->assertSame($originalPassword, $user->password);
        $this->assertFalse($user->is_banned);
        $this->assertFalse($user->is_silenced);
        $this->assertNull($user->timeout_until);
        $this->assertFalse($user->will_be_deleted);
    }

    public function test_cosmetic_name_tag_updates_only_validated_name_tag_field(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
            'item_id' => $this->createItem()->id,
            'unlocks_feature' => FeatureType::COSMETIC_NAME_TAGS->value,
            'unlocks_skill' => false,
        ]);
        QuestsCompleted::create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);
        $user = $character->user;
        $originalEmail = $user->email;
        $originalPassword = $user->password;

        $response = $this->actingAs($user)->post(route('user.settings.cosmetic-name-tag', [
            'user' => $user->id,
        ]), [
            'name_tag' => NameTag::EXPLORER->value,
            'email' => 'attacker@example.com',
            'password' => 'compromised',
            'is_banned' => true,
            'is_silenced' => true,
            'timeout_until' => now()->addYear(),
            'will_be_deleted' => true,
        ])->response;

        $response->assertSessionHas('success', 'Updated Name Tag options');
        $user = $user->refresh();
        $this->assertSame(NameTag::EXPLORER->value, $user->name_tag);
        $this->assertSame($originalEmail, $user->email);
        $this->assertSame($originalPassword, $user->password);
        $this->assertFalse($user->is_banned);
        $this->assertFalse($user->is_silenced);
        $this->assertNull($user->timeout_until);
        $this->assertFalse($user->will_be_deleted);
    }
}
