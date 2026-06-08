<?php

namespace Tests\Console;

use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\FeatureTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class RepairQuestFeatureRewardsTest extends TestCase
{
    use CreateInventorySets, CreateNpc, CreateQuest, RefreshDatabase;

    public function test_command_does_not_double_grant_extra_sets_when_character_already_has_twenty(): void
    {
        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::EXTEND_SETS,
        ]);

        for ($setNumber = 0; $setNumber < 20; $setNumber++) {
            $this->createInventorySet(['character_id' => $character->id]);
        }

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $this->assertEquals(0, $this->artisan('repair:quest-feature-rewards'));
        $this->assertEquals(20, $character->fresh()->inventorySets()->count());
    }

    public function test_command_adds_missing_extra_sets_only_when_quest_was_completed(): void
    {
        $npc = $this->createNpc();
        $characterWithQuest = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $characterWithoutQuest = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::EXTEND_SETS,
        ]);

        for ($setNumber = 0; $setNumber < 10; $setNumber++) {
            $this->createInventorySet(['character_id' => $characterWithQuest->id]);
            $this->createInventorySet(['character_id' => $characterWithoutQuest->id]);
        }

        $characterWithQuest->questsCompleted()->create([
            'character_id' => $characterWithQuest->id,
            'quest_id' => $quest->id,
        ]);

        $this->assertEquals(0, $this->artisan('repair:quest-feature-rewards'));
        $this->assertEquals(20, $characterWithQuest->fresh()->inventorySets()->count());
        $this->assertEquals(10, $characterWithoutQuest->fresh()->inventorySets()->count());
    }

    public function test_command_fixes_missing_extended_backpack_only_when_quest_was_completed(): void
    {
        $npc = $this->createNpc();
        $characterWithQuest = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $characterWithoutQuest = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::EXTENDED_BACKPACK,
        ]);

        $characterWithQuest->questsCompleted()->create([
            'character_id' => $characterWithQuest->id,
            'quest_id' => $quest->id,
        ]);

        $this->assertEquals(0, $this->artisan('repair:quest-feature-rewards'));
        $this->assertEquals(150, $characterWithQuest->fresh()->inventory_max);
        $this->assertEquals(75, $characterWithoutQuest->fresh()->inventory_max);
    }

    public function test_command_reports_reincarnation_as_quest_log_based(): void
    {
        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::REINCARNATION,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        Artisan::call('repair:quest-feature-rewards');

        $this->assertStringContainsString('reincarnation: access is quest-log based, skipped 1', Artisan::output());
    }

    public function test_command_reports_capital_cities_as_quest_log_based(): void
    {
        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::CAPITAL_CITIES,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        Artisan::call('repair:quest-feature-rewards');

        $this->assertStringContainsString('capital_cities: access is quest-log based, skipped 1', Artisan::output());
    }

    public function test_command_reports_capital_city_gold_bars_as_quest_log_based(): void
    {
        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::CAPITAL_CITY_GOLD_BARS,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        Artisan::call('repair:quest-feature-rewards');

        $this->assertStringContainsString('capital_city_gold_bars: access is quest-log based, skipped 1', Artisan::output());
    }

    public function test_command_reports_cosmetic_text_as_quest_log_based(): void
    {
        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::COSMETIC_TEXT,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        Artisan::call('repair:quest-feature-rewards');

        $this->assertStringContainsString('cosmetic_text: access is quest-log based, skipped 1', Artisan::output());
    }

    public function test_command_does_not_create_fake_quest_log_unlock_records(): void
    {
        $npc = $this->createNpc();

        $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::REINCARNATION,
        ]);
        $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::COSMETIC_TEXT,
        ]);
        $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::COSMETIC_NAME_TAGS,
        ]);
        $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::COSMETIC_RACE_CHANGER,
        ]);
        $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::CAPITAL_CITIES,
        ]);
        $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_feature' => FeatureTypes::CAPITAL_CITY_GOLD_BARS,
        ]);

        Artisan::call('repair:quest-feature-rewards');

        $this->assertEquals(0, QuestsCompleted::count());
    }
}
