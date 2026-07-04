<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterClassRank;
use Tests\Traits\CreateCharacterClassSpecialitiesEquipped;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;

class CharacterTopsApiTest extends TestCase
{
    use CreateCharacterClassRank;
    use CreateCharacterClassSpecialitiesEquipped;
    use CreateClass;
    use CreateGameClassSpecial;
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotCallCharacterTopsApi(): void
    {
        $this->assertSame(302, $this->call('GET', '/api/game/tops/characters')->getStatusCode());
    }

    public function testAuthenticatedUsersCanCallCharacterTopsApiWithoutPrivateFields(): void
    {
        $user = User::factory()->create(['email' => 'private@example.com', 'password' => 'secret', 'remember_token' => 'token', 'ip_address' => '127.0.0.1']);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Public Hero', 'level' => 10]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

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

    public function testCharacterProfileOverviewReturnsWhitelistedPublicFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Profile Hero']);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Profile Hero', $data['name']);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testCharacterProfileOverviewDoesNotExposeIp(): void
    {
        $user = User::factory()->create(['ip_address' => '10.0.0.1']);
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');

        $this->assertStringNotContainsString('10.0.0.1', $response->getContent());
        $this->assertStringNotContainsString('ip_address', $response->getContent());
    }

    public function testCharacterProfileOverviewDoesNotExposeRememberToken(): void
    {
        $user = User::factory()->create(['remember_token' => 'private-token']);
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');

        $this->assertStringNotContainsString('private-token', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }

    public function testCharacterFullProfileEndpointRequiresAuthentication(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAuthenticatedNonOwnerCanInspectReadOnlyFullProfile(): void
    {
        $viewer = User::factory()->create();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->user->update([
            'email' => 'owner@example.com',
            'password' => 'secret',
            'remember_token' => 'private-token',
            'ip_address' => '10.0.0.1',
        ]);
        $item = Item::factory()->create([
            'name' => 'Public Inspect Sword',
            'type' => 'weapon',
            'base_damage' => 10,
            'base_ac' => 2,
            'holy_stacks' => 3,
        ]);
        InventorySlot::factory()->create([
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

    public function testEquipmentPayloadIncludesItemColorationAndModalSafeFields(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $user = $character->user;
        $item = Item::factory()->create([
            'name' => 'Color Sword',
            'type' => 'weapon',
            'base_damage' => 15,
            'base_ac' => 5,
            'description' => 'Safe public description.',
        ]);
        InventorySlot::factory()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
            'equipped' => true,
            'position' => 'weapon',
        ]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $itemPayload = json_decode($response->getContent(), true)['equipment']['items'][0];

        $this->assertArrayHasKey('item_name', $itemPayload);
        $this->assertArrayHasKey('item_id', $itemPayload);
        $this->assertArrayHasKey('attached_affixes_count', $itemPayload);
        $this->assertArrayHasKey('str_modifier', $itemPayload);
        $this->assertArrayHasKey('base_damage', $itemPayload);
        $this->assertArrayHasKey('base_ac_mod', $itemPayload);
        $this->assertArrayHasKey('item_atonements', $itemPayload);
        $this->assertArrayHasKey('item_prefix', $itemPayload);
        $this->assertArrayHasKey('item_suffix', $itemPayload);
        $this->assertArrayHasKey('socket_amount', $itemPayload);
        $this->assertArrayHasKey('has_holy_stacks_applied', $itemPayload);
        $this->assertArrayHasKey('sockets', $itemPayload);
        $this->assertArrayHasKey('item_skill', $itemPayload);
        $this->assertSame('Color Sword', $itemPayload['item_name']);
    }

    public function testProfileIncludesCompletedQuestDetailsAndRealCompletionChart(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $npc = Npc::factory()->create();
        $quest = Quest::factory()->create([
            'name' => 'Public Quest Detail',
            'npc_id' => $npc->id,
            'before_completion_description' => 'Quest before text.',
            'after_completion_description' => 'Quest after text.',
            'reward_gold' => 100,
            'reward_xp' => 200,
        ]);
        QuestsCompleted::factory()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
            'guide_quest_id' => null,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $quests = json_decode($response->getContent(), true)['quests'];

        $this->assertSame('Public Quest Detail', $quests['completed_quests'][0]['name']);
        $this->assertSame('Quest before text.', $quests['completed_quests'][0]['before_completion_description']);
        $this->assertSame(100, $quests['completed_quests'][0]['rewards']['gold']);
        $this->assertSame('hours', $quests['completion_chart']['granularity']);
        $this->assertSame('Quests', $quests['completion_chart']['series'][0]['label']);
        $this->assertSame(1, $quests['completion_chart']['series'][0]['points'][0]['value']);
    }

    public function testProfileIncludesCompletedGuideQuestDetails(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $guideQuest = GuideQuest::factory()->create([
            'name' => 'Public Guide Quest Detail',
            'intro_text' => 'Guide intro.',
            'instructions' => 'Guide instructions.',
            'gold_reward' => 50,
            'xp_reward' => 75,
        ]);
        QuestsCompleted::factory()->create([
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
        $this->assertSame(50, $quests['completed_guide_quests'][0]['rewards']['gold']);
        $this->assertSame('Guide Quests', $quests['completion_chart']['series'][1]['label']);
        $this->assertSame(1, $quests['completion_chart']['series'][1]['points'][0]['value']);
    }

    public function testProfileIncludesKingdomAndAnalyticsChartPayloads(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->call('GET', '/api/game/tops/characters/'.$character->id.'/profile');
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('kingdom_summary_chart', $data['kingdoms']);
        $this->assertArrayHasKey('resource_totals_chart', $data['kingdoms']);
        $this->assertArrayHasKey('top_kingdoms_chart', $data['kingdoms']);
        $this->assertArrayNotHasKey('map_distribution', $data['kingdoms']);
        $this->assertArrayHasKey('analytics_summary_chart', $data['analytics']);
        $this->assertSame('Analytics Summary', $data['analytics']['analytics_summary_chart']['source']);
    }

    public function testClassMasteryPayloadIncludesActiveLeveledEquippedAndUnlockedDetails(): void
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
        $this->assertSame('Focused Strike', $activePayload['equipped_specialties'][0]['name']);
        $this->assertSame('Public Mage', $leveledPayload['class']);
        $this->assertSame('Stored Flame', $leveledPayload['unlocked_specialties'][0]['name']);
        $this->assertArrayNotHasKey('equip_url', $activePayload['equipped_specialties'][0]);
        $this->assertArrayNotHasKey('train_url', $activePayload);
    }

    public function testStatsPayloadIncludesSafeBreakdownData(): void
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
}
