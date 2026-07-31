<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateUser;

class BatchCraftingPanelControllerTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacter, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateMap, CreateUser, RefreshDatabase;

    public function test_status_does_not_return_visible_panel_when_no_batch_is_running_or_undismissed(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertFalse($response->json('active'));
        $this->assertFalse($response->json('is_running'));
        $this->assertFalse($response->json('is_visible'));
        $this->assertFalse($response->json('can_cancel'));
        $this->assertFalse($response->json('can_dismiss'));
        $this->assertNull($response->json('batch'));
    }

    public function test_maxed_craft_experience_skill_is_omitted_from_options_payload(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->skills()->create(['game_skill_id' => $armourCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertNotContains('weapon', collect($response->json('craft_experience_options'))->pluck('value')->all());
        $this->assertContains('armour', collect($response->json('craft_experience_options'))->pluck('value')->all());
    }

    public function test_craft_experience_options_are_absent_when_all_crafting_experience_skills_are_maxed(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->skills()->create(['game_skill_id' => $armourCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->skills()->create(['game_skill_id' => $ringCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 100, 'xp_max' => 100]);
        $character->skills()->create(['game_skill_id' => $spellCrafting->id, 'character_id' => $character->id, 'level' => 5, 'xp' => 100, 'xp_max' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame([], $response->json('craft_experience_options'));
    }

    public function test_specific_item_status_payload_includes_progress_and_inventory_fields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $item = $this->createItem(['name' => 'Cracked Bone Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'cost' => 7, 'can_craft' => true]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'crafted_count' => 2,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 2],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame(5, $response->json('batch.requested_amount'));
        $this->assertSame(2, $response->json('batch.completed_amount'));
        $this->assertSame(3, $response->json('batch.remaining_amount'));
        $this->assertSame(40, $response->json('batch.progress_percent'));
        $this->assertSame(0, $response->json('batch.inventory_count'));
        $this->assertSame(10, $response->json('batch.inventory_max'));
        $this->assertSame(0, $response->json('batch.inventory_percent'));
        $this->assertSame(14, $response->json('batch.gold_spent'));
        $this->assertSame('Cracked Bone Dagger', $response->json('batch.current_item_name'));
    }

    public function test_specific_item_completed_status_includes_safe_crafted_item_snapshot(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $item = $this->createItem(['name' => 'Finished Batch Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'cost' => 5, 'can_craft' => true]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
            'crafted_count' => 3,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 3, 'craft_specific_count' => 3],
            'action_log' => [[
                'ts' => now()->toJSON(),
                'action_type' => 'craft',
                'status' => 'crafted',
                'crafted_item' => ['name' => 'Finished Batch Dagger', 'type' => 'dagger', 'can_view' => false, 'base_damage' => 4, 'crafted_at' => now()->toJSON()],
            ]],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame(3, $response->json('batch.completed_amount'));
        $this->assertSame(15, $response->json('batch.gold_spent'));
        $this->assertSame('Finished Batch Dagger', $response->json('batch.crafted_item_snapshots.0.display_name'));
        $this->assertSame(4, $response->json('batch.crafted_item_snapshots.0.snapshot.base_damage'));
        $this->assertFalse($response->json('batch.crafted_item_snapshots.0.snapshot.can_view'));
    }

    public function test_craft_experience_status_includes_skills_current_item_and_capacity_fields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 3, 'xp' => 40, 'xp_max' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'craft_experience_current_item_snapshot' => ['name' => 'Training Dagger', 'type' => 'dagger', 'can_view' => false]],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame('Training Dagger', $response->json('batch.current_item_name'));
        $this->assertSame('Weapon Crafting', $response->json('batch.skills_being_trained.0.name'));
        $this->assertSame(3, $response->json('batch.skills_being_trained.0.level'));
        $this->assertSame(40, $response->json('batch.skills_being_trained.0.current_xp'));
        $this->assertSame(100, $response->json('batch.skills_being_trained.0.next_level_xp'));
        $this->assertArrayHasKey('batch_crafting_set', $response->json('batch'));
        $this->assertArrayHasKey('inventory_percent', $response->json('batch'));
    }

    public function test_completed_panel_remains_until_dismissed(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $this->actingAs($user)->get(route('batch-crafting.status', ['character' => $character]));

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->whereNull('panel_dismissed_at')->first()->panel_dismissed_at);
    }

    public function test_dismissed_panel_no_longer_returns(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $this->actingAs($user)->post(route('batch-crafting.dismiss', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('panel_dismissed_at')->first());
    }

    public function test_status_returns_active_true_when_batch_running(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertTrue($response->json('active'));
        $this->assertNotNull($response->json('batch'));
        $this->assertNotNull($response->json('batch.id'));
    }

    public function test_status_returns_active_panel_data_after_starting_craft_for_experience(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character->skills()->create(['game_skill_id' => $weaponCrafting->id, 'character_id' => $character->id, 'level' => 2, 'xp' => 25, 'xp_max' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertTrue($response->json('active'));
        $this->assertTrue($response->json('is_visible'));
        $this->assertSame(BatchCraftingType::CRAFT->value, $response->json('batch.batch_type'));
    }

    public function test_status_returns_timer_fields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayHasKey('elapsed_seconds', $response->json('batch'));
        $this->assertArrayHasKey('remaining_seconds', $response->json('batch'));
        $this->assertArrayHasKey('elapsed_human', $response->json('batch'));
        $this->assertArrayHasKey('remaining_human', $response->json('batch'));
    }

    public function test_status_allows_cancel_for_active_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));
        $this->assertTrue($response->json('active'));

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('cancelled_at')->first());
    }

    public function test_status_returns_completed_visible_panel_data_after_cancel(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));
        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertTrue($response->json('completed'));
        $this->assertTrue($response->json('is_visible'));
    }

    public function test_status_returns_not_visible_after_dismiss(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'completed_at' => now(), 'ended_reason' => BatchCraftingEndReason::DIED->value, 'panel_dismissed_at' => null]);

        $this->actingAs($user)->post(route('batch-crafting.dismiss', ['character' => $character]));
        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertFalse($response->json('is_visible'));
    }

    public function test_status_endpoint_query_does_not_combine_broad_or_with_order_by(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $unsafeQueries = [];

        DB::listen(function ($query) use (&$unsafeQueries): void {
            $sql = strtolower($query->sql);

            if (
                str_starts_with($sql, 'select') &&
                str_contains($sql, 'batch_craftings') &&
                str_contains($sql, 'panel_dismissed_at') &&
                str_contains($sql, ' or ') &&
                str_contains($sql, 'order by')
            ) {
                $unsafeQueries[] = $sql;
            }
        });

        $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertEmpty($unsafeQueries);
    }

    public function test_status_returns_inventory_bar_fields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayHasKey('inventory_count', $response->json('batch'));
        $this->assertArrayHasKey('inventory_max', $response->json('batch'));
        $this->assertArrayHasKey('inventory_percent', $response->json('batch'));
    }

    public function test_status_returns_crafted_set_fields_for_craft_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayHasKey('batch_crafting_set', $response->json('batch'));
        $this->assertArrayHasKey('current_slots', $response->json('batch.batch_crafting_set'));
        $this->assertArrayHasKey('max_slots', $response->json('batch.batch_crafting_set'));
    }

    public function test_status_returns_alchemy_bag_fields_for_alchemy_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayHasKey('alchemy_bag_count', $response->json('batch'));
        $this->assertArrayHasKey('alchemy_bag_max', $response->json('batch'));
        $this->assertArrayHasKey('alchemy_bag_remaining', $response->json('batch'));
    }

    public function test_event_batch_data_always_includes_can_craft_and_enchant_for_event_keys(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayHasKey('can_craft_for_event', $response->json('event_batch'));
        $this->assertArrayHasKey('can_enchant_for_event', $response->json('event_batch'));
    }

    public function test_event_batch_data_includes_skill_maxed_flags(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayHasKey('crafting_skills_maxed', $response->json('event_batch'));
        $this->assertArrayHasKey('alchemy_maxed', $response->json('event_batch'));
        $this->assertArrayHasKey('trinketry_maxed', $response->json('event_batch'));
        $this->assertArrayHasKey('enchanting_maxed', $response->json('event_batch'));
    }

    public function test_can_craft_for_event_is_true_when_on_crafting_event_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['inventory_max' => 10, 'gold' => 100]);
        $schedule = ScheduledEvent::factory()->create(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::CRAFT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_crafts' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $character = $character->refresh();

        $response = $this->actingAs($character->user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertTrue($response->json('event_batch.can_craft_for_event'));
    }

    public function test_can_craft_for_event_is_false_when_no_active_crafting_event(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertFalse($response->json('event_batch.can_craft_for_event'));
    }

    public function test_can_enchant_for_event_is_true_when_on_enchanting_event_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['inventory_max' => 10, 'gold' => 100]);
        $schedule = ScheduledEvent::factory()->create(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $character = $character->refresh();

        $response = $this->actingAs($character->user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertTrue($response->json('event_batch.can_enchant_for_event'));
    }

    public function test_can_enchant_for_event_is_false_when_no_active_enchanting_event(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertFalse($response->json('event_batch.can_enchant_for_event'));
    }

    public function test_event_batch_data_has_no_can_alchemy_for_event_key(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayNotHasKey('can_alchemy_for_event', $response->json('event_batch'));
    }

    public function test_event_enchant_status_returns_readable_phase_and_fallback_counts(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'progress' => [
                'event_mode' => true,
                'event_action' => 'enchant',
                'event_enchant_phase' => 'craft_fallback_set',
                'event_fallback_phase' => 'craft_fallback_set',
                'event_fallback_crafted_this_tick' => 6,
                'event_fallback_enchanted_this_tick' => 6,
            ],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame('Crafting fallback items', $response->json('batch.event_current_phase_label'));
        $this->assertSame(6, $response->json('batch.event_fallback_crafted_this_tick'));
        $this->assertSame(6, $response->json('batch.event_fallback_enchanted_this_tick'));
        $this->assertSame('craft fallback items and double-enchant them', $response->json('batch.next_action'));
    }

    public function test_status_endpoint_returns_craft_enchant_set_work_progress_and_retry_state_contract(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_requested' => 23,
                'craft_enchant_set_craft_index' => 23,
                'craft_enchant_set_enchant_index' => 8,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_completed_work_units' => 31,
                'craft_enchant_set_total_work_units' => 69,
                'craft_enchant_set_completed_final_count' => 0,
            ],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame(23, $response->json('batch.craft_enchant_set_craft_completed_count'));
        $this->assertSame(8, $response->json('batch.craft_enchant_set_enchant_completed_count'));
        $this->assertSame(0, $response->json('batch.craft_enchant_set_finalize_completed_count'));
        $this->assertSame(69, $response->json('batch.craft_enchant_set_total_work_units'));
        $this->assertSame(31, $response->json('batch.craft_enchant_set_completed_work_units'));
        $this->assertSame(38, $response->json('batch.craft_enchant_set_remaining_work_units'));
        $this->assertSame(44, $response->json('batch.craft_enchant_set_overall_percent'));
        $this->assertArrayHasKey('retry_state', $response->json('batch'));
        $this->assertArrayHasKey('active', $response->json('batch.retry_state'));
        $this->assertArrayHasKey('failed_count', $response->json('batch.retry_state'));
        $this->assertArrayHasKey('delay_seconds', $response->json('batch.retry_state'));
        $this->assertArrayHasKey('failure_reason', $response->json('batch.retry_state'));
        $this->assertArrayHasKey('failure_phase', $response->json('batch.retry_state'));
        $this->assertArrayHasKey('failure_action', $response->json('batch.retry_state'));
        $this->assertFalse($response->json('batch.retry_state.active'));
    }

    public function test_status_endpoint_exposes_output_destination_label(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory'],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertSame('Inventory', $response->json('batch.output_destination_label'));
    }

    public function test_status_endpoint_does_not_expose_kept_output_committed_flag(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory'],
        ]);

        $response = $this->actingAs($user)->call('GET', route('batch-crafting.status', ['character' => $character]));

        $this->assertArrayNotHasKey('kept_output_committed', $response->json('batch') ?? []);
    }
}
