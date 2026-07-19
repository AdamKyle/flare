<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;

class CharacterSheetBaseInfoTransformerTest extends TestCase
{
    use CreateCharacterAutomation;
    use CreateGameMap;
    use CreateItem;
    use RefreshDatabase;

    private Character $character;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testTransformContainsExplorationActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addSeconds(300),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals([
            'type' => AutomationType::EXPLORING,
            'name' => 'Exploration',
            'timer_seconds' => 300,
        ], $data['active_automation']);
    }

    public function testTransformContainsDelveActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addSeconds(600),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals([
            'type' => AutomationType::DELVE,
            'name' => 'Delve',
            'timer_seconds' => 600,
        ], $data['active_automation']);
    }

    public function testTransformContainsFactionLoyaltyActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addSeconds(900),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals([
            'type' => AutomationType::FACTION_LOYALTY,
            'name' => 'Faction Loyalty',
            'timer_seconds' => 900,
        ], $data['active_automation']);
    }

    public function testTransformHasNullActiveAutomationWhenNoAutomationIsRunning(): void
    {
        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
        $this->assertEquals(0, $data['automation_completed_at']);
    }

    public function testTransformContainsZeroTimeoutModifierBonuses(): void
    {
        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals(0.0, $data['fight_time_out_mod_bonus']);
        $this->assertEquals(0.0, $data['movement_time_out_mod_bonus']);
    }

    public function testTransformContainsTimeoutModifierBonuses(): void
    {
        $skill = $this->character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('name', 'Fighters Timeout');
        })->first();
        $skill->baseSkill()->update([
            'fight_time_out_mod_bonus_per_level' => 0.1,
            'move_time_out_mod_bonus_per_level' => 0.2,
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertEquals(0.1, $data['fight_time_out_mod_bonus']);
        $this->assertEquals(0.2, $data['movement_time_out_mod_bonus']);
    }

    public function testTransformTreatsNullTimeoutModifierBonusesAsZero(): void
    {
        $skill = $this->character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('name', 'Fighters Timeout');
        })->first();
        $skill->baseSkill()->update([
            'fight_time_out_mod_bonus_per_level' => null,
            'move_time_out_mod_bonus_per_level' => null,
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertEquals(0.0, $data['fight_time_out_mod_bonus']);
        $this->assertEquals(0.0, $data['movement_time_out_mod_bonus']);
    }

    public function testTransformDoesNotDisplayExplorationForUnknownAutomationType(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => 99,
            'completed_at' => now()->addSeconds(300),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
    }

    public function testTransformHasNullActiveAutomationWhenAutomationIsCompleted(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subSecond(),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
        $this->assertEquals(0, $data['automation_completed_at']);
    }

    public function testTransformSetsAutomationRunningFalseForExpiredAutomation(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subSecond(),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertFalse($data['is_automation_running']);
    }

    public function testTransformSetsDelveRunningFalseForExpiredDelve(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->subSecond(),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertFalse($data['is_delve_running']);
    }

    public function testTransformAllowsQueenOfHeartsInHellWithQuestItem(): void
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'path' => 'hell.png',
            'default' => false,
        ]);
        $queenQuestItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::QUEEN_OF_HEARTS,
        ]);
        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(gameMap: $hell)
            ->inventoryManagement()
            ->giveItem($queenQuestItem)
            ->getCharacter();

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertTrue($data['can_access_queen']);
    }

    public function testTransformDoesNotAllowQueenOfHeartsOutsideHellWithQuestItem(): void
    {
        $queenQuestItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::QUEEN_OF_HEARTS,
        ]);
        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($queenQuestItem)
            ->getCharacter();

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['can_access_queen']);
    }

    public function testTransformDoesNotAllowQueenOfHeartsInHellWithoutQuestItem(): void
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'path' => 'hell.png',
            'default' => false,
        ]);
        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(gameMap: $hell)
            ->getCharacter();

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['can_access_queen']);
    }

    public function testTransformReturnsFalseForMapTypeFieldsWhenCharacterMapIsNull(): void
    {
        $this->character->map()->delete();

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['can_access_hell_forged']);
        $this->assertFalse($data['can_access_purgatory_chains']);
        $this->assertFalse($data['can_access_labyrinth_oracle']);
        $this->assertFalse($data['can_access_twisted_earth']);
        $this->assertFalse($data['can_access_queen']);
    }

    public function testBatchCraftingTimeOutUsesOneMinutePendingCountdownForDelayedModes(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'started_at' => now(),
            'progress' => ['tick_delay_seconds' => 60, 'holy_oil_mode' => 'selected'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertEquals(60, $data['batch_crafting_time_out']);
    }

    public function testBatchCraftingTimeOutUsesEightHourRemainingTimerForEightHourModes(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(8),
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertEquals(28800, $data['batch_crafting_time_out']);
    }

    public function testActiveBatchCraftingLookupExcludesCancelledBatches(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'cancelled_at' => now(),
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['is_batch_crafting_running']);
    }

    public function testActiveBatchCraftingLookupExcludesCompletedBatches(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'completed_at' => now(),
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['is_batch_crafting_running']);
    }

    public function testActiveBatchCraftingLookupReturnsNewestBatchWithoutStartedAtSort(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'started_at' => now(),
            'completed_at' => now(),
            'progress' => ['tick_delay_seconds' => 60, 'holy_oil_mode' => 'selected'],
        ]);
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(8),
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertTrue($data['is_batch_crafting_running']);
        $this->assertEquals(28800, $data['batch_crafting_time_out']);
    }

    public function testActiveBatchCraftingTransformerQueryNeverCombinesBroadOrWithOrderBy(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(8),
            'progress' => ['craft_mode' => 'experience'],
        ]);
        $unsafeBatchCraftingQueries = [];

        DB::listen(function ($query) use (&$unsafeBatchCraftingQueries): void {
            $sql = strtolower($query->sql);

            if (
                str_starts_with($sql, 'select') &&
                str_contains($sql, 'batch_craftings') &&
                str_contains($sql, 'panel_dismissed_at') &&
                str_contains($sql, ' or ') &&
                str_contains($sql, 'order by')
            ) {
                $unsafeBatchCraftingQueries[] = $sql;
            }
        });

        resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertEmpty($unsafeBatchCraftingQueries);
    }

    public function testVisibleBatchCraftingIsTrueForActiveBatch(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(8),
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertTrue($data['is_batch_crafting_visible']);
    }

    public function testVisibleBatchCraftingIsTrueWhenNotYetDismissed(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'completed_at' => now(),
            'panel_dismissed_at' => null,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertTrue($data['is_batch_crafting_visible']);
    }

    public function testVisibleBatchCraftingIsFalseWhenCompletedAndDismissed(): void
    {
        BatchCrafting::factory()->create([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'started_at' => now(),
            'completed_at' => now(),
            'panel_dismissed_at' => now(),
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['is_batch_crafting_visible']);
    }

    public function testDelveVisibleIsTrueForActiveDelve(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addSeconds(600),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertTrue($data['is_delve_visible']);
    }

    public function testDelveVisibleIsTrueForCompletedUndismissedDelve(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'completed_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertTrue($data['is_delve_visible']);
    }

    public function testDelveVisibleIsFalseAfterDismissed(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'completed_at' => now(),
            'panel_dismissed_at' => now(),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertFalse($data['is_delve_visible']);
    }

    public function testDelveVisibleMatchesBetweenTransformerAndStatusUpdateEvent(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'completed_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $character = $this->character->refresh();

        $transformerData = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character);
        $event = new UpdateCharacterStatus($character);

        $this->assertTrue($transformerData['is_delve_visible']);
        $this->assertSame($transformerData['is_delve_visible'], $event->characterStatuses['is_delve_visible']);
    }
}
