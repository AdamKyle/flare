<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterClassRank;
use Tests\Traits\CreateCharacterClassSpecialitiesEquipped;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateQuestsCompleted;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class CharacterTopsApiTest extends TestCase
{
    use CreateCharacter;
    use CreateCharacterClassRank;
    use CreateCharacterClassSpecialitiesEquipped;
    use CreateClass;
    use CreateGameClassSpecial;
    use CreateGameMap;
    use CreateGuideQuest;
    use CreateInventorySlot;
    use CreateItem;
    use CreateMap;
    use CreateNpc;
    use CreateQuest;
    use CreateQuestsCompleted;
    use CreateUser;
    use CreateUserLoginDuration;
    use RefreshDatabase;

    public function test_unauthenticated_users_cannot_call_character_tops_api(): void
    {
        $this->assertSame(302, $this->call('GET', '/api/game/tops/characters')->getStatusCode());
    }

    public function test_authenticated_users_can_call_character_tops_api_without_private_fields(): void
    {
        $user = $this->createUser(['email' => 'private@example.com', 'password' => 'secret', 'remember_token' => 'token', 'ip_address' => '127.0.0.1']);
        $this->createCharacter(['user_id' => $user->id, 'name' => 'Public Hero', 'level' => 10]);
        $this->createUserLoginDuration(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Public Hero', $data['rows'][0]['character_name']);
        $this->assertSame('/game/tops/characters/'.$data['rows'][0]['character_id'], $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString('private@example.com', $response->getContent());
        $this->assertStringNotContainsString('password', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
        $this->assertStringNotContainsString('ip_address', $response->getContent());
    }

    public function test_character_profile_overview_returns_whitelisted_public_fields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'name' => 'Profile Hero']);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Profile Hero', $data['name']);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function test_signed_in_user_can_request_another_public_characters_stat_break_down(): void
    {
        $viewer = $this->createUser();
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($viewer)->call('GET', '/api/game/tops/characters/'.$character->id.'/stat-break-down', ['stat_type' => 'str']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('break_down', $data);
    }

    public function test_signed_in_user_can_request_another_public_characters_specific_stat_break_down(): void
    {
        $viewer = $this->createUser();
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($viewer)->call('GET', '/api/game/tops/characters/'.$character->id.'/specific-attribute-break-down', ['type' => 'health', 'is_voided' => 0]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('break_down', $data);
    }

    public function test_tops_stat_break_down_request_does_not_mutate_character(): void
    {
        $viewer = $this->createUser();
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $goldBeforeRequest = $character->gold;

        $this->actingAs($viewer)->call('GET', '/api/game/tops/characters/'.$character->id.'/stat-break-down', ['stat_type' => 'str']);

        $this->assertSame($goldBeforeRequest, $character->refresh()->gold);
    }

    public function test_private_character_sheet_stat_break_down_route_remains_owner_authorized(): void
    {
        $otherCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($otherCharacter->user)->call('GET', '/api/character-sheet/'.$character->id.'/stat-break-down', ['stat_type' => 'str']);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_character_profile_overview_does_not_expose_ip(): void
    {
        $user = $this->createUser(['ip_address' => '10.0.0.1']);
        $character = $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');

        $this->assertStringNotContainsString('10.0.0.1', $response->getContent());
        $this->assertStringNotContainsString('ip_address', $response->getContent());
    }

    public function test_character_profile_overview_does_not_expose_remember_token(): void
    {
        $user = $this->createUser(['remember_token' => 'private-token']);
        $character = $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');

        $this->assertStringNotContainsString('private-token', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }

    public function test_character_full_profile_endpoint_requires_authentication(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_authenticated_non_owner_can_inspect_read_only_full_profile(): void
    {
        $viewer = $this->createUser();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->user->update([
            'email' => 'owner@example.com',
            'password' => 'secret',
            'remember_token' => 'private-token',
            'ip_address' => '10.0.0.1',
        ]);
        $item = $this->createItem([
            'name' => 'Public Inspect Sword',
            'type' => 'weapon',
            'base_damage' => 10,
            'base_ac' => 2,
            'holy_stacks' => 3,
        ]);
        $this->createInventorySlot([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
            'equipped' => true,
            'position' => 'weapon',
        ]);

        $response = $this->actingAs($viewer)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('overview', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('equipment', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('quests', $data);
        $this->assertArrayHasKey('activity', $data);
        $this->assertArrayHasKey('analytics', $data);
        $this->assertStringNotContainsString('owner@example.com', $response->getContent());
        $this->assertStringNotContainsString('password', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
        $this->assertStringNotContainsString('10.0.0.1', $response->getContent());
        $this->assertStringNotContainsString('inventory_slots', $response->getContent());
        $this->assertStringNotContainsString('inventory_sets', $response->getContent());
        $this->assertStringNotContainsString('equip_url', $response->getContent());
        $this->assertStringNotContainsString('unequip_url', $response->getContent());
        $this->assertStringNotContainsString('switch_class_url', $response->getContent());
        $this->assertStringNotContainsString('train_url', $response->getContent());
    }

    public function test_equipment_payload_includes_item_coloration_and_modal_safe_fields(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $item = $this->createItem([
            'name' => 'Color Sword',
            'type' => 'weapon',
            'base_damage' => 15,
            'base_ac' => 5,
            'description' => 'Safe public description.',
        ]);
        $this->createInventorySlot([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
            'equipped' => true,
            'position' => 'weapon',
        ]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $itemPayload = json_decode($response->getContent(), true)['equipment']['items'][0];

        $this->assertArrayHasKey('item_name', $itemPayload);
        $this->assertArrayHasKey('item_id', $itemPayload);
        $this->assertArrayHasKey('affix_count', $itemPayload);
        $this->assertArrayHasKey('str_modifier', $itemPayload);
        $this->assertArrayHasKey('base_damage', $itemPayload);
        $this->assertArrayHasKey('base_ac_mod', $itemPayload);
        $this->assertArrayHasKey('item_atonements', $itemPayload);
        $this->assertArrayHasKey('item_prefix', $itemPayload);
        $this->assertArrayHasKey('item_suffix', $itemPayload);
        $this->assertArrayHasKey('socket_amount', $itemPayload);
        $this->assertArrayHasKey('holy_stacks_applied', $itemPayload);
        $this->assertArrayHasKey('sockets', $itemPayload);
        $this->assertSame('Color Sword', $itemPayload['item_name']);
        $this->assertSame($item->getTotalDamage(), $itemPayload['base_damage']);
    }

    public function test_profile_includes_completed_quest_details_and_real_completion_chart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $quest = $this->createQuest([
            'name' => 'Public Quest Detail',
            'npc_id' => $npc->id,
            'before_completion_description' => 'Quest before text.',
            'after_completion_description' => 'Quest after text.',
            'reward_gold' => 100,
            'reward_xp' => 200,
        ]);
        $this->createQuestsCompleted([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
            'guide_quest_id' => null,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $quests = json_decode($response->getContent(), true)['quests'];

        $this->assertSame('Public Quest Detail', $quests['completed_quests'][0]['name']);
        $this->assertTrue($quests['completed_quests'][0]['inspected_character_completed']);
        $this->assertSame('Quest before text.', $quests['completed_quests'][0]['details']['before_completion_description']);
        $this->assertSame(100, $quests['completed_quests'][0]['details']['reward_gold']);
        $this->assertSame('hours', $quests['completion_chart']['granularity']);
        $this->assertSame('Quests', $quests['completion_chart']['series'][0]['label']);
        $this->assertSame(1, $quests['completion_chart']['series'][0]['points'][0]['value']);
    }

    public function test_profile_includes_completed_guide_quest_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'name' => 'Public Guide Quest Detail',
            'intro_text' => 'Guide intro.',
            'instructions' => 'Guide instructions.',
            'gold_reward' => 50,
            'xp_reward' => 75,
        ]);
        $this->createQuestsCompleted([
            'character_id' => $character->id,
            'quest_id' => null,
            'guide_quest_id' => $guideQuest->id,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $quests = json_decode($response->getContent(), true)['quests'];

        $this->assertSame('Public Guide Quest Detail', $quests['completed_guide_quests'][0]['name']);
        $this->assertSame('Guide intro.', $quests['completed_guide_quests'][0]['intro_text']);
        $this->assertSame('Guide instructions.', $quests['completed_guide_quests'][0]['instructions']);
        $this->assertSame(50, $quests['completed_guide_quests'][0]['gold_reward']);
        $this->assertSame('Guide Quests', $quests['completion_chart']['series'][1]['label']);
        $this->assertSame(1, $quests['completion_chart']['series'][1]['points'][0]['value']);
    }

    public function test_profile_excludes_kingdom_charts_and_includes_analytics_chart_payloads(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $data = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('kingdom_summary_chart', $data['kingdoms']);
        $this->assertArrayNotHasKey('kingdom_treasury_chart', $data['kingdoms']);
        $this->assertArrayNotHasKey('kingdom_gold_bars_chart', $data['kingdoms']);
        $this->assertArrayNotHasKey('resource_totals_chart', $data['kingdoms']);
        $this->assertArrayNotHasKey('top_kingdoms_chart', $data['kingdoms']);
        $this->assertArrayNotHasKey('map_distribution', $data['kingdoms']);
        $this->assertArrayHasKey('analytics_kills_chart', $data['analytics']);
        $this->assertArrayHasKey('analytics_runs_chart', $data['analytics']);
        $this->assertArrayNotHasKey('analytics_summary_chart', $data['analytics']);
        $this->assertArrayNotHasKey('tables', $data['analytics']);
        $this->assertArrayNotHasKey('summary', $data['analytics']);
    }

    public function test_class_mastery_payload_includes_active_leveled_equipped_and_unlocked_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $activeRank = $this->createCharacterClassRank([
            'character_id' => $character->id,
            'game_class_id' => $character->game_class_id,
            'current_xp' => 50,
            'required_xp' => 100,
            'level' => 3,
        ]);
        CharacterClassRankWeaponMastery::create([
            'character_class_rank_id' => $activeRank->id,
            'weapon_type' => 'weapon',
            'current_xp' => 12,
            'required_xp' => 20,
            'level' => 2,
        ]);
        $activeSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'name' => 'Focused Strike',
            'description' => 'Read-only active specialty.',
        ]);
        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $activeSpecial->id,
            'level' => 2,
            'current_xp' => 5,
            'required_xp' => 10,
            'equipped' => true,
        ]);
        $leveledClass = $this->createClass(['name' => 'Public Mage']);
        $this->createCharacterClassRank([
            'character_id' => $character->id,
            'game_class_id' => $leveledClass->id,
            'current_xp' => 10,
            'required_xp' => 100,
            'level' => 2,
        ]);
        $unlockedSpecial = $this->createGameClassSpecial([
            'game_class_id' => $leveledClass->id,
            'name' => 'Stored Flame',
            'description' => 'Read-only unlocked specialty.',
        ]);
        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $unlockedSpecial->id,
            'level' => 2,
            'current_xp' => 2,
            'required_xp' => 10,
            'equipped' => false,
        ]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $classRanks = json_decode($response->getContent(), true)['skills']['class_ranks'];
        $activePayload = collect($classRanks)->first(function (array $classRank) use ($character): bool {
            return $classRank['class_id'] === $character->game_class_id
                && $classRank['level'] === 3;
        });
        $leveledPayload = collect($classRanks)->firstWhere('class_id', $leveledClass->id);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($activePayload['is_active']);
        $this->assertTrue(collect($activePayload['weapon_masteries'])->contains('name', 'Weapon'));
        $this->assertSame('Public Mage', $leveledPayload['class']);
        $this->assertArrayNotHasKey('current_class_skills', $activePayload);
        $this->assertArrayNotHasKey('equipped_specialties', $activePayload);
        $this->assertArrayNotHasKey('unlocked_specialties', $leveledPayload);
        $this->assertArrayNotHasKey('train_url', $activePayload);
    }

    public function test_stats_payload_includes_safe_breakdown_data(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $stats = json_decode($response->getContent(), true)['stats'];

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('base_stats', $stats);
        $this->assertArrayHasKey('modded_stats', $stats);
        $this->assertArrayHasKey('stat_breakdown', $stats);
        $this->assertArrayHasKey('weapon_damage', $stats['stat_breakdown']);
        $this->assertArrayHasKey('description', $stats['stat_breakdown']['weapon_damage']);
        $this->assertArrayNotHasKey('mutation_url', $stats['stat_breakdown']['weapon_damage']);
    }

    public function test_quest_details_payload_does_not_expose_mutation_urls_or_private_viewer_fields(): void
    {
        $owner = $this->createUser();
        $character = $this->createCharacter(['user_id' => $owner->id, 'name' => 'Owner Hero']);
        $viewer = $this->createUser(['email' => 'viewer-private@example.com']);
        $this->createCharacter(['user_id' => $viewer->id, 'name' => 'Viewer Hero']);
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $quest = $this->createQuest(['name' => 'Mutation Safe Quest', 'npc_id' => $npc->id]);
        $this->createQuestsCompleted(['character_id' => $character->id, 'quest_id' => $quest->id, 'created_at' => now()]);

        $response = $this->actingAs($viewer)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertFalse($data['quests']['completed_quests'][0]['viewer_has_completed']);
        $this->assertStringNotContainsString('viewer-private@example.com', $response->getContent());
        $this->assertStringNotContainsString('hand_in_url', $response->getContent());
    }

    public function test_guide_quest_details_payload_does_not_expose_mutation_urls_or_private_viewer_fields(): void
    {
        $owner = $this->createUser();
        $character = $this->createCharacter(['user_id' => $owner->id, 'name' => 'Owner Hero']);
        $viewer = $this->createUser(['email' => 'guide-viewer-private@example.com']);
        $viewerCharacter = $this->createCharacter(['user_id' => $viewer->id, 'name' => 'Viewer Hero']);
        $viewerGameMap = $this->createGameMap();
        $this->createMap(['character_id' => $viewerCharacter->id, 'game_map_id' => $viewerGameMap->id]);
        $guideQuest = $this->createGuideQuest(['name' => 'Mutation Safe Guide Quest']);
        $this->createQuestsCompleted(['character_id' => $character->id, 'guide_quest_id' => $guideQuest->id, 'created_at' => now()]);

        $response = $this->actingAs($viewer)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertFalse($data['quests']['completed_guide_quests'][0]['viewer_has_completed']);
        $this->assertStringNotContainsString('guide-viewer-private@example.com', $response->getContent());
        $this->assertStringNotContainsString('hand_in_url', $response->getContent());
    }

    public function test_stats_endpoint_preserves_existing_fields_and_includes_preloaded_character_sheet_data(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/stats');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('base_stats', $data);
        $this->assertArrayHasKey('modded_stats', $data);
        $this->assertArrayHasKey('resistances', $data);
        $this->assertArrayHasKey('stat_details', $data);
        $this->assertArrayHasKey('resistance_info', $data);
        $this->assertArrayHasKey('elemental_atonement', $data);
        $this->assertArrayHasKey('resurrection_chance', $data);
    }

    public function test_stats_endpoint_for_character_with_no_inventory_returns_null_detail_fields_and_does_not_create_an_inventory(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/stats');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('stat_details', $data);
        $this->assertArrayHasKey('resistance_info', $data);
        $this->assertArrayHasKey('elemental_atonement', $data);
        $this->assertArrayHasKey('resurrection_chance', $data);
        $this->assertNull($data['stat_details']);
        $this->assertNull($data['resistance_info']);
        $this->assertNull($data['elemental_atonement']);
        $this->assertSame(0, $data['resurrection_chance']);
        $this->assertFalse(Inventory::where('character_id', $character->id)->exists());
    }

    public function test_reincarnation_endpoint_preserves_existing_keys_and_includes_reincarnation_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/reincarnation');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('times_reincarnated', $data);
        $this->assertArrayHasKey('reincarnated_stat_increase', $data);
        $this->assertArrayHasKey('xp_penalty', $data);
        $this->assertArrayHasKey('base_stat_mod', $data);
        $this->assertArrayHasKey('base_damage_stat_mod', $data);
        $this->assertArrayHasKey('reincarnation_details', $data);
    }

    public function test_skills_endpoint_includes_class_ranks_offered_and_class_rank_specialties(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/skills');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('regular_skills', $data);
        $this->assertArrayHasKey('crafting_skills', $data);
        $this->assertArrayHasKey('class_ranks', $data);
        $this->assertArrayHasKey('kingdom_passives', $data);
        $this->assertArrayHasKey('class_ranks_offered', $data);
        $this->assertArrayHasKey('class_rank_specialties', $data);
    }
}
