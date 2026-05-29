<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_transform_contains_exploration_active_automation_metadata(): void
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

    public function test_transform_contains_delve_active_automation_metadata(): void
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

    public function test_transform_contains_faction_loyalty_active_automation_metadata(): void
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

    public function test_transform_has_null_active_automation_when_no_automation_is_running(): void
    {
        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
        $this->assertEquals(0, $data['automation_completed_at']);
    }

    public function test_transform_contains_zero_timeout_modifier_bonuses(): void
    {
        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals(0.0, $data['fight_time_out_mod_bonus']);
        $this->assertEquals(0.0, $data['movement_time_out_mod_bonus']);
    }

    public function test_transform_contains_timeout_modifier_bonuses(): void
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

    public function test_transform_treats_null_timeout_modifier_bonuses_as_zero(): void
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

    public function test_transform_does_not_display_exploration_for_unknown_automation_type(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => 99,
            'completed_at' => now()->addSeconds(300),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
    }

    public function test_transform_has_null_active_automation_when_automation_is_completed(): void
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

    public function test_transform_allows_queen_of_hearts_in_hell_with_quest_item(): void
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

    public function test_transform_does_not_allow_queen_of_hearts_outside_hell_with_quest_item(): void
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

    public function test_transform_does_not_allow_queen_of_hearts_in_hell_without_quest_item(): void
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
}
