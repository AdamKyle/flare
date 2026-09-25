<?php

namespace Tests\Feature\Game\Core\Controllers;

use App\Game\Core\Values\FeatureType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRace;

class SettingsControllerTest extends TestCase
{
    use CreateNpc, CreateQuest, CreateRace, RefreshDatabase;

    public function test_cosmetic_race_changer_requires_unlock_quest_completion(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newRace = $this->createRace(['name' => 'Locked Target Race']);

        $response = $this->actingAs($character->user)->call(
            'POST',
            '/settings/'.$character->user->id.'/cosmetic-race-changer',
            ['race_id' => $newRace->id],
        );

        $response->assertSessionHas('error');
        $this->assertNotSame($newRace->id, $character->refresh()->game_race_id);
    }

    public function test_cosmetic_race_changer_changes_the_game_race_id(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newRace = $this->createRace(['name' => 'Unlocked Target Race']);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureType::COSMETIC_RACE_CHANGER->value,
            'npc_id' => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $response = $this->actingAs($character->user)->call(
            'POST',
            '/settings/'.$character->user->id.'/cosmetic-race-changer',
            ['race_id' => $newRace->id],
        );

        $response->assertSessionHas('success');
        $this->assertSame($newRace->id, $character->refresh()->game_race_id);
    }

    public function test_cosmetic_race_changer_does_not_change_character_base_stats(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newRace = $this->createRace(['name' => 'Stat Safe Race']);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureType::COSMETIC_RACE_CHANGER->value,
            'npc_id' => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $originalStats = [
            'str' => $character->str,
            'dur' => $character->dur,
            'dex' => $character->dex,
            'chr' => $character->chr,
            'int' => $character->int,
            'agi' => $character->agi,
            'focus' => $character->focus,
        ];

        $this->actingAs($character->user)->call(
            'POST',
            '/settings/'.$character->user->id.'/cosmetic-race-changer',
            ['race_id' => $newRace->id],
        );

        $character->refresh();

        $this->assertSame($originalStats['str'], $character->str);
        $this->assertSame($originalStats['dur'], $character->dur);
        $this->assertSame($originalStats['dex'], $character->dex);
        $this->assertSame($originalStats['chr'], $character->chr);
        $this->assertSame($originalStats['int'], $character->int);
        $this->assertSame($originalStats['agi'], $character->agi);
        $this->assertSame($originalStats['focus'], $character->focus);
    }

    public function test_gem_progression_xp_message_settings_default_to_true(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertTrue($character->user->show_global_gem_progression_xp_messages);
        $this->assertTrue($character->user->show_personal_gem_progression_xp_messages);
    }

    public function test_chat_settings_disables_both_gem_progression_xp_messages_when_checkboxes_are_missing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->actingAs($character->user)->call(
            'POST',
            '/settings/'.$character->user->id.'/chat-settings',
            [],
        );

        $user = $character->user->refresh();

        $this->assertFalse($user->show_global_gem_progression_xp_messages);
        $this->assertFalse($user->show_personal_gem_progression_xp_messages);
    }

    public function test_chat_settings_enables_both_gem_progression_xp_messages_when_checkboxes_are_checked(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->actingAs($character->user)->call(
            'POST',
            '/settings/'.$character->user->id.'/chat-settings',
            [
                'show_global_gem_progression_xp_messages' => '1',
                'show_personal_gem_progression_xp_messages' => '1',
            ],
        );

        $user = $character->user->refresh();

        $this->assertTrue($user->show_global_gem_progression_xp_messages);
        $this->assertTrue($user->show_personal_gem_progression_xp_messages);
    }
}
