<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\Gem;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameClassSpecial;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\ItemSocket;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\Skill;
use App\Flare\Models\TopsMonthlySnapshot;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Game\Tops\Services\CharacterTopsInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTopsInspectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testOverviewReturnsPublicCharacterFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Public']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->overview($character);

        $this->assertSame('Public', $data['name']);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function testStatsReturnsExplicitStatGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->stats($character);

        $this->assertArrayHasKey('base_stats', $data);
        $this->assertArrayHasKey('modded_stats', $data);
        $this->assertArrayHasKey('resistances', $data);
        $this->assertArrayHasKey('stat_breakdown', $data);
    }

    public function testStatsForCharacterWithNoInventoryReturnsNullDetailFieldsInsteadOfEmptyArrays(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->stats($character);

        $this->assertArrayHasKey('base_stats', $data);
        $this->assertArrayHasKey('modded_stats', $data);
        $this->assertArrayHasKey('stat_details', $data);
        $this->assertArrayHasKey('resistance_info', $data);
        $this->assertArrayHasKey('elemental_atonement', $data);
        $this->assertArrayHasKey('resurrection_chance', $data);
        $this->assertNull($data['stat_details']);
        $this->assertNull($data['resistance_info']);
        $this->assertNull($data['elemental_atonement']);
        $this->assertSame(0.0, $data['resurrection_chance']);
    }

    public function testStatBreakDownMatchesStatModifierDetailsForStat(): void
    {
        $character = (new \Tests\Setup\Character\CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $expected = $this->app->make(\App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails::class)->setCharacter($character)->forStat('str');

        $data = $this->app->make(CharacterTopsInspectionService::class)->statBreakDown($character, 'str');

        $this->assertSame($expected, $data);
    }

    public function testSpecificStatBreakDownMatchesBuildSpecificBreakDown(): void
    {
        $character = (new \Tests\Setup\Character\CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $expected = $this->app->make(\App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails::class)->setCharacter($character)->buildSpecificBreakDown('health', false);

        $data = $this->app->make(CharacterTopsInspectionService::class)->specificStatBreakDown($character, 'health', false);

        $this->assertSame($expected, $data);
    }

    public function testEquipmentReturnsExplicitEquipmentRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->equipment($character);

        $this->assertArrayHasKey('source', $data);
        $this->assertArrayHasKey('set_name', $data);
        $this->assertArrayHasKey('items', $data);
    }

    public function testSkillsReturnsExplicitSkillGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);

        $this->assertArrayHasKey('regular_skills', $data);
        $this->assertArrayHasKey('passive_skills', $data);
        $this->assertArrayHasKey('class_ranks', $data);
        $this->assertArrayHasKey('class_specialties_equipped', $data);
    }

    public function testSkillsReturnsReadableTopsSkillRowShape(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameSkill = GameSkill::factory()->create(['name' => 'Fire Magic', 'can_train' => true, 'max_level' => 10]);
        Skill::factory()->create(['character_id' => $character->id, 'game_skill_id' => $gameSkill->id, 'level' => 5, 'xp' => 100]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);
        $skillRow = $data['regular_skills'][0];

        $this->assertSame($character->id, $skillRow['character_id']);
        $this->assertSame(10, $skillRow['max_level']);
        $this->assertFalse($skillRow['is_class_skill']);
        $this->assertIsString($skillRow['skill_type']);
        $this->assertSame('Training', $skillRow['skill_type']);
        $this->assertArrayNotHasKey('train_url', $skillRow);
    }

    public function testSkillsIncludePublicDetailsRequiredBySkillInformation(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameSkill = GameSkill::factory()->create(['name' => 'Public Accuracy', 'description' => 'Public skill detail.', 'can_train' => true, 'max_level' => 10]);
        Skill::factory()->create(['character_id' => $character->id, 'game_skill_id' => $gameSkill->id, 'level' => 5, 'xp' => 100]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);
        $details = $data['regular_skills'][0]['details'];

        $this->assertSame('Public skill detail.', $details['description']);
        $this->assertArrayHasKey('skill_bonus_break_down', $details);
        $this->assertArrayHasKey('skill_xp_bonus_break_down', $details);
    }

    public function testSkillsPublicDetailsExcludeMutationAndOwnerData(): void
    {
        $user = User::factory()->create(['email' => 'private-skill-owner@example.com']);
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameSkill = GameSkill::factory()->create(['can_train' => true]);
        Skill::factory()->create(['character_id' => $character->id, 'game_skill_id' => $gameSkill->id, 'level' => 2]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);
        $encodedSkill = json_encode($data['regular_skills'][0]);

        $this->assertStringNotContainsString('private-skill-owner@example.com', $encodedSkill);
        $this->assertStringNotContainsString('train_url', $encodedSkill);
        $this->assertStringNotContainsString('stop_training_url', $encodedSkill);
    }

    public function testFactionsReturnsExplicitFactionGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->factions($character);

        $this->assertArrayHasKey('factions', $data);
        $this->assertArrayHasKey('loyalties', $data);
        $this->assertArrayHasKey('npcs', $data);
        $this->assertArrayHasKey('automation_summary', $data);
    }

    public function testActivityReturnsExplicitActivityGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->activity($character);

        $this->assertArrayHasKey('login_count_chart', $data);
        $this->assertArrayHasKey('login_duration_chart', $data);
        $this->assertArrayNotHasKey('exploration_run_count', $data);
        $this->assertArrayNotHasKey('quest_completion_count', $data);
    }

    public function testActivityDoesNotIncludeRemovedRollingLoginAndDelveOutcomeFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->activity($character);

        $this->assertArrayNotHasKey('login_duration_7_days', $data);
        $this->assertArrayNotHasKey('login_duration_14_days', $data);
        $this->assertArrayNotHasKey('login_duration_30_days', $data);
        $this->assertArrayNotHasKey('login_count_7_days', $data);
        $this->assertArrayNotHasKey('login_count_14_days', $data);
        $this->assertArrayNotHasKey('login_count_30_days', $data);
        $this->assertArrayNotHasKey('delve_outcome_counts', $data);
    }

    public function testActivityLoginCountChartReflectsRealUserLoginDurationRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subDays(2), 'last_activity' => now()->subDays(2), 'last_heart_beat' => now()->subDays(2), 'duration_in_seconds' => 3600]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subDay(), 'last_activity' => now()->subDay(), 'last_heart_beat' => now()->subDay(), 'duration_in_seconds' => 1800]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->activity($character);
        $countPoints = $data['login_count_chart']['series'][0]['points'];

        $this->assertSame(1, $countPoints[0]['value']);
        $this->assertSame(2, end($countPoints)['value']);
    }

    public function testActivityLoginDurationChartConvertsSecondsToHours(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subDay(), 'last_activity' => now()->subDay(), 'last_heart_beat' => now()->subDay(), 'duration_in_seconds' => 7200]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->activity($character);

        $this->assertSame('Login Duration (Hours)', $data['login_duration_chart']['series'][0]['label']);
        $this->assertSame(2.0, $data['login_duration_chart']['series'][0]['points'][0]['value']);
    }

    public function testQuestsCompletionChartNormalQuestPointsAreCumulative(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $questOne = Quest::factory()->create(['npc_id' => $npc->id]);
        $questTwo = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questOne->id, 'created_at' => now()->subDays(2)]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questTwo->id, 'created_at' => now()->subDay()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);
        $points = $data['completion_chart']['series'][0]['points'];

        $this->assertSame(1, $points[0]['value']);
        $this->assertSame(2, end($points)['value']);
    }

    public function testQuestsCompletionChartGuideQuestPointsAreCumulative(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $guideQuestOne = GuideQuest::factory()->create();
        $guideQuestTwo = GuideQuest::factory()->create();
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuestOne->id, 'created_at' => now()->subDays(2)]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuestTwo->id, 'created_at' => now()->subDay()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);
        $points = $data['completion_chart']['series'][1]['points'];

        $this->assertSame(1, $points[0]['value']);
        $this->assertSame(2, end($points)['value']);
    }

    public function testQuestsCompletionChartKeepsNormalAndGuideQuestSeriesSeparate(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id]);
        $guideQuestOne = GuideQuest::factory()->create();
        $guideQuestTwo = GuideQuest::factory()->create();
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id, 'created_at' => now()->subDay()]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuestOne->id, 'created_at' => now()->subDay()]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuestTwo->id, 'created_at' => now()->subDay()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);

        $this->assertSame('Quests', $data['completion_chart']['series'][0]['label']);
        $this->assertSame('Guide Quests', $data['completion_chart']['series'][1]['label']);
        $this->assertSame(1, end($data['completion_chart']['series'][0]['points'])['value']);
        $this->assertSame(2, end($data['completion_chart']['series'][1]['points'])['value']);
    }

    public function testQuestsSummaryChartInspectedCharacterSeriesIsCumulative(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $character->created_at = now()->subDays(10);
        $character->save();

        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $questOne = Quest::factory()->create(['npc_id' => $npc->id]);
        $questTwo = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questOne->id, 'created_at' => now()->subDays(5)]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questTwo->id, 'created_at' => now()->subDays(3)]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);
        $points = $data['summary_chart']['series'][0]['points'];

        $this->assertSame($character->name.' Completed Quests', $data['summary_chart']['series'][0]['label']);
        $this->assertSame(1, $points[0]['value']);
        $this->assertSame(2, end($points)['value']);
    }

    public function testQuestsSummaryChartPointsAreChronological(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $character->created_at = now()->subDays(10);
        $character->save();

        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $questOne = Quest::factory()->create(['npc_id' => $npc->id]);
        $questTwo = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questOne->id, 'created_at' => now()->subDays(6)]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questTwo->id, 'created_at' => now()->subDay()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);
        $dates = collect($data['summary_chart']['series'][0]['points'])->pluck('date')->values()->all();
        $sortedDates = collect($dates)->sort()->values()->all();

        $this->assertSame($sortedDates, $dates);
    }

    public function testQuestsSummaryChartAverageSeriesExcludesInspectedCharacterCompletions(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Inspected Hero']);
        $character->created_at = now()->subDays(10);
        $character->save();

        $otherUser = User::factory()->create();
        $otherCharacter = Character::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Hero']);
        $otherCharacter->created_at = now()->subDays(10);
        $otherCharacter->save();

        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id, 'created_at' => now()->subDays(5)]);
        QuestsCompleted::factory()->create(['character_id' => $otherCharacter->id, 'quest_id' => $quest->id, 'created_at' => now()->subDays(5)]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);
        $lastAveragePoint = collect($data['summary_chart']['series'][1]['points'])->last();

        $this->assertSame(1.0, $lastAveragePoint['value']);
    }

    public function testQuestsSummaryChartAverageAccountsForCharacterWithZeroCompletions(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Inspected Hero']);
        $character->created_at = now()->subDays(10);
        $character->save();

        $activeOtherUser = User::factory()->create();
        $activeOtherCharacter = Character::factory()->create(['user_id' => $activeOtherUser->id, 'name' => 'Active Other Hero']);
        $activeOtherCharacter->created_at = now()->subDays(10);
        $activeOtherCharacter->save();

        $idleOtherUser = User::factory()->create();
        $idleOtherCharacter = Character::factory()->create(['user_id' => $idleOtherUser->id, 'name' => 'Idle Other Hero']);
        $idleOtherCharacter->created_at = now()->subDays(10);
        $idleOtherCharacter->save();

        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $activeOtherCharacter->id, 'quest_id' => $quest->id, 'created_at' => now()->subDays(3)]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character);
        $lastAveragePoint = collect($data['summary_chart']['series'][1]['points'])->last();

        $this->assertSame(0.5, $lastAveragePoint['value']);
    }

    public function testQuestsViewerNormalQuestStateReflectsNonCompletingViewer(): void
    {
        $ownerUser = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $ownerUser->id, 'name' => 'Owner Hero']);
        $viewerUser = User::factory()->create();
        Character::factory()->create(['user_id' => $viewerUser->id, 'name' => 'Viewer Hero']);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id, 'created_at' => now()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $viewerUser);
        $questDetail = $data['completed_quests'][0];

        $this->assertFalse($questDetail['viewer_has_completed']);
        $this->assertArrayNotHasKey('hand_in_url', $questDetail);
    }

    public function testQuestsViewerRequiredQuestCompleteReflectsViewerOwnCompletion(): void
    {
        $ownerUser = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $ownerUser->id, 'name' => 'Owner Hero']);
        $viewerUser = User::factory()->create();
        $viewerCharacter = Character::factory()->create(['user_id' => $viewerUser->id, 'name' => 'Viewer Hero']);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $requiredQuest = Quest::factory()->create(['npc_id' => $npc->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id, 'required_quest_id' => $requiredQuest->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id, 'created_at' => now()]);
        QuestsCompleted::factory()->create(['character_id' => $viewerCharacter->id, 'quest_id' => $requiredQuest->id, 'created_at' => now()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $viewerUser);
        $questDetail = $data['completed_quests'][0];

        $this->assertTrue($questDetail['viewer_required_quest_complete']);
    }

    public function testQuestsViewerGuideQuestStateReflectsNonCompletingViewer(): void
    {
        $ownerUser = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $ownerUser->id, 'name' => 'Owner Hero']);
        $viewerUser = User::factory()->create();
        $viewerCharacter = Character::factory()->create(['user_id' => $viewerUser->id, 'name' => 'Viewer Hero']);
        $viewerGameMap = GameMap::factory()->create();
        Map::factory()->create(['character_id' => $viewerCharacter->id, 'game_map_id' => $viewerGameMap->id]);
        $guideQuest = GuideQuest::factory()->create();
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuest->id, 'created_at' => now()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $viewerUser);
        $guideQuestDetail = $data['completed_guide_quests'][0];

        $this->assertFalse($guideQuestDetail['viewer_has_completed']);
        $this->assertArrayNotHasKey('hand_in_url', $guideQuestDetail);
    }

    public function testGuideQuestViewerCompletedStateUsesCompletionRecord(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id, 'name' => 'Completed Owner']);
        $viewer = User::factory()->create();
        $viewerCharacter = Character::factory()->create(['user_id' => $viewer->id, 'name' => 'Completed Viewer']);
        $guideQuest = GuideQuest::factory()->create();
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuest->id]);
        QuestsCompleted::factory()->create(['character_id' => $viewerCharacter->id, 'guide_quest_id' => $guideQuest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $viewer);

        $this->assertTrue($data['completed_guide_quests'][0]['viewer_has_completed']);
    }

    public function testGuideQuestViewerUnlockedStateDoesNotRequireHandInReadiness(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id, 'name' => 'Unlocked Owner']);
        $viewer = User::factory()->create();
        Character::factory()->create(['user_id' => $viewer->id, 'name' => 'Unlocked Viewer', 'level' => 1]);
        $guideQuest = GuideQuest::factory()->create(['required_level' => 100, 'unlock_at_level' => null, 'only_during_event' => null, 'parent_id' => null]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $guideQuest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $viewer);

        $this->assertTrue($data['completed_guide_quests'][0]['viewer_has_unlocked']);
    }

    public function testGuideQuestViewerLockedStateExcludesUnreachedQuest(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id, 'name' => 'Locked Owner']);
        $viewer = User::factory()->create();
        Character::factory()->create(['user_id' => $viewer->id, 'name' => 'Locked Viewer']);
        GuideQuest::factory()->create(['unlock_at_level' => null, 'only_during_event' => null, 'parent_id' => null]);
        $lockedGuideQuest = GuideQuest::factory()->create(['unlock_at_level' => null, 'only_during_event' => null, 'parent_id' => null]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'guide_quest_id' => $lockedGuideQuest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $viewer);

        $this->assertFalse($data['completed_guide_quests'][0]['viewer_has_unlocked']);
    }

    public function testLeveledUnequippedClassSpecialtyIsCurrentProgress(): void
    {
        $user = User::factory()->create();
        $gameClass = GameClass::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'game_class_id' => $gameClass->id]);
        CharacterClassRank::factory()->create(['character_id' => $character->id, 'game_class_id' => $gameClass->id, 'level' => 2]);
        $specialty = GameClassSpecial::factory()->create(['game_class_id' => $gameClass->id, 'name' => 'Stored Flame']);
        CharacterClassSpecialtiesEquipped::factory()->create(['character_id' => $character->id, 'game_class_special_id' => $specialty->id, 'level' => 3, 'equipped' => false]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);

        $this->assertSame('Stored Flame', $data['class_ranks'][0]['unlocked_specialties'][0]['name']);
    }

    public function testQuestDetailPayloadRetainsPublicModalFields(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id]);
        $gameMap = GameMap::factory()->create(['name' => 'Surface']);
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id, 'real_name' => 'Public Quest NPC']);
        $quest = Quest::factory()->create(['npc_id' => $npc->id, 'name' => 'Public Quest', 'before_completion_description' => 'Before text']);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $owner);
        $details = $data['completed_quests'][0]['details'];

        $this->assertSame('Public Quest NPC', $details['npc']['real_name']);
        $this->assertSame('Surface', $details['npc']['game_map']['name']);
        $this->assertSame('Before text', $details['before_completion_description']);
    }

    public function testQuestDetailPayloadExcludesPrivateAndMutationFields(): void
    {
        $owner = User::factory()->create(['email' => 'private-owner@example.com']);
        $character = Character::factory()->create(['user_id' => $owner->id]);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $owner);
        $encodedDetails = json_encode($data['completed_quests'][0]['details']);

        $this->assertStringNotContainsString('private-owner@example.com', $encodedDetails);
        $this->assertStringNotContainsString('hand_in_url', $encodedDetails);
        $this->assertStringNotContainsString('mutation_url', $encodedDetails);
    }

    public function testKingdomsResourceTotalsChartIncludesRealHistoricalSnapshotPoint(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        TopsMonthlySnapshot::factory()->create([
            'board_type' => 'kingdoms',
            'metric_key' => 'kingdom_count',
            'character_id' => $character->id,
            'period_start' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            'rank' => 1,
            'snapshot_data' => [
                'kingdom_count' => 2,
                'capital_count' => 1,
                'total_treasury' => 5000,
                'total_gold_bars' => 20,
                'total_current_population' => 300,
                'total_current_stone' => 100,
                'total_current_wood' => 200,
                'total_current_clay' => 300,
                'total_current_iron' => 400,
                'total_current_steel' => 500,
            ],
        ]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->kingdoms($character);
        $stoneSeries = collect($data['resource_totals_chart']['series'])->firstWhere('label', 'Stone');

        $this->assertSame(100, $stoneSeries['points'][0]['value']);
    }

    public function testKingdomsChartsAreEmptyWhenCharacterHasNoSnapshots(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->kingdoms($character);

        $this->assertSame([], $data['resource_totals_chart']['series'][0]['points']);
        $this->assertSame([], $data['kingdom_summary_chart']['series'][0]['points']);
    }

    public function testKingdomsTableRowsIncludeResourceFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $gameMap = GameMap::factory()->create();
        Kingdom::factory()->create([
            'character_id' => $character->id,
            'game_map_id' => $gameMap->id,
            'npc_owned' => false,
            'current_stone' => 111,
            'current_wood' => 222,
            'current_clay' => 333,
            'current_iron' => 444,
            'current_steel' => 555,
        ]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->kingdoms($character);
        $kingdomRow = $data['kingdoms'][0];

        $this->assertSame(111, $kingdomRow['current_stone']);
        $this->assertSame(555, $kingdomRow['current_steel']);
    }

    public function testKingdomsResponseDoesNotIncludeTopKingdomsChart(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->kingdoms($character);

        $this->assertArrayNotHasKey('top_kingdoms_chart', $data);
    }

    public function testAnalyticsChartsUseDatedCumulativeSeriesFromRealRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        ExplorationLog::factory()->create(['character_id' => $character->id, 'kills' => 5, 'started_at' => now()->subDays(2)]);
        ExplorationLog::factory()->create(['character_id' => $character->id, 'kills' => 3, 'started_at' => now()->subDay()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->analytics($character);
        $killsPoints = $data['analytics_kills_chart']['series'][0]['points'];

        $this->assertSame(5, $killsPoints[0]['value']);
        $this->assertSame(8, end($killsPoints)['value']);
    }

    public function testAnalyticsChartsAreEmptyWhenNoActivityExists(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->analytics($character);

        $this->assertSame([], $data['analytics_kills_chart']['series'][0]['points']);
        $this->assertArrayNotHasKey('tables', $data);
        $this->assertArrayNotHasKey('analytics_summary_chart', $data);
    }

    public function testAnalyticsRunsChartReflectsRealDelveAndQuestRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        DelveExploration::factory()->create(['character_id' => $character->id, 'started_at' => now()->subDays(2)]);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $quest = Quest::factory()->create(['npc_id' => $npc->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id, 'created_at' => now()->subDay()]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->analytics($character);
        $runsSeries = $data['analytics_runs_chart']['series'];
        $delveRunsPoints = collect($runsSeries)->firstWhere('label', 'Delve Runs')['points'];
        $questsCompletedPoints = collect($runsSeries)->firstWhere('label', 'Quests Completed')['points'];

        $this->assertSame(1, end($delveRunsPoints)['value']);
        $this->assertSame(1, end($questsCompletedPoints)['value']);
    }

    public function testEquipmentItemUsesRealTransformedDamageValueFromItemTransformer(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $inventory = Inventory::factory()->create(['character_id' => $character->id]);
        $prefix = ItemAffix::factory()->create(['type' => 'prefix', 'base_damage_mod' => 0.5]);
        $item = Item::factory()->create(['type' => 'weapon', 'base_damage' => 10, 'item_prefix_id' => $prefix->id]);
        InventorySlot::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $item->id, 'equipped' => true, 'position' => 'weapon']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->equipment($character);
        $itemPayload = $data['items'][0];

        $this->assertSame(15, $itemPayload['base_damage']);
    }

    public function testEquipmentItemIncludesRealAtonementDetailsFromGemSockets(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $inventory = Inventory::factory()->create(['character_id' => $character->id]);
        $gem = Gem::factory()->create(['primary_atonement_amount' => 5.0]);
        $item = Item::factory()->create(['type' => 'weapon', 'base_damage' => 10, 'socket_count' => 1]);
        ItemSocket::create(['item_id' => $item->id, 'gem_id' => $gem->id]);
        InventorySlot::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $item->id, 'equipped' => true, 'position' => 'weapon']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->equipment($character);
        $itemPayload = $data['items'][0];

        $this->assertSame('Ice', $itemPayload['item_atonements']['elemental_damage']['name']);
        $this->assertSame(5.0, $itemPayload['item_atonements']['elemental_damage']['amount']);
    }

    public function testEquipmentSocketsIncludeAttachedGemDetailFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $inventory = Inventory::factory()->create(['character_id' => $character->id]);
        $gem = Gem::factory()->create(['name' => 'Public Gem', 'tier' => 3, 'primary_atonement_amount' => 0.25]);
        $item = Item::factory()->create(['type' => 'weapon', 'socket_count' => 1]);
        ItemSocket::create(['item_id' => $item->id, 'gem_id' => $gem->id]);
        InventorySlot::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $item->id, 'equipped' => true, 'position' => 'weapon']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->equipment($character);
        $socket = $data['items'][0]['sockets'][0];

        $this->assertSame('Public Gem', $socket['name']);
        $this->assertSame(3, $socket['tier']);
        $this->assertArrayHasKey('primary_atonement_name', $socket);
    }

    public function testEquipmentSocketsExcludeMutationAndPrivateOwnerData(): void
    {
        $user = User::factory()->create(['email' => 'private-gem-owner@example.com']);
        $character = Character::factory()->create(['user_id' => $user->id]);
        $inventory = Inventory::factory()->create(['character_id' => $character->id]);
        $gem = Gem::factory()->create();
        $item = Item::factory()->create(['type' => 'weapon', 'socket_count' => 1]);
        ItemSocket::create(['item_id' => $item->id, 'gem_id' => $gem->id]);
        InventorySlot::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $item->id, 'equipped' => true, 'position' => 'weapon']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->equipment($character);
        $encodedSockets = json_encode($data['items'][0]['sockets']);

        $this->assertStringNotContainsString('private-gem-owner@example.com', $encodedSockets);
        $this->assertStringNotContainsString('mutation_url', $encodedSockets);
        $this->assertStringNotContainsString('inventory_id', $encodedSockets);
    }

    public function testQuestItemLocationUsesNestedPublicMapShape(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id]);
        $gameMap = GameMap::factory()->create(['name' => 'Location Plane']);
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $questItem = Item::factory()->create(['type' => 'quest']);
        Location::factory()->create(['game_map_id' => $gameMap->id, 'quest_reward_item_id' => $questItem->id, 'name' => 'Public Location']);
        $quest = Quest::factory()->create(['npc_id' => $npc->id, 'item_id' => $questItem->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $owner);
        $location = $data['completed_quests'][0]['details']['item']['locations'][0];

        $this->assertSame('Public Location', $location['name']);
        $this->assertSame('Location Plane', $location['map']['name']);
        $this->assertArrayNotHasKey('character_id', $location);
        $this->assertArrayNotHasKey('mutation_url', $location);
    }

    public function testStatsPreservesExistingFieldsAndIncludesPreloadedCharacterSheetData(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        Inventory::factory()->create(['character_id' => $character->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->stats($character->refresh());

        $this->assertArrayHasKey('base_stats', $data);
        $this->assertArrayHasKey('modded_stats', $data);
        $this->assertArrayHasKey('resistances', $data);
        $this->assertArrayHasKey('stat_breakdown', $data);
        $this->assertArrayHasKey('stat_details', $data);
        $this->assertArrayHasKey('resistance_info', $data);
        $this->assertArrayHasKey('elemental_atonement', $data);
        $this->assertArrayHasKey('resurrection_chance', $data);
        $this->assertSame($data['base_stats']['str'], $data['stat_details']['str']);
        $this->assertSame(0.0, $data['resistance_info']['spell_evasion']);
    }

    public function testReincarnationPreservesExistingKeysAndIncludesReincarnationDetails(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'times_reincarnated' => 2,
            'reincarnated_stat_increase' => 5,
            'xp_penalty' => 0.1,
            'base_stat_mod' => 0.2,
            'base_damage_stat_mod' => 0.3,
        ]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->reincarnation($character);

        $this->assertSame(2, $data['times_reincarnated']);
        $this->assertSame(5, $data['reincarnated_stat_increase']);
        $this->assertArrayHasKey('reincarnation_details', $data);
        $this->assertSame(2, $data['reincarnation_details']['reincarnated_times']);
        $this->assertSame(5, $data['reincarnation_details']['reincarnated_stat_increase']);
        $this->assertSame(0.1, $data['reincarnation_details']['xp_penalty']);
        $this->assertSame(0.2, $data['reincarnation_details']['base_stat_mod']);
        $this->assertSame(0.3, $data['reincarnation_details']['base_damage_stat_mod']);
    }

    public function testSkillsIncludesClassRanksOfferedAndClassRankSpecialties(): void
    {
        $user = User::factory()->create();
        $gameClass = GameClass::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'game_class_id' => $gameClass->id]);
        CharacterClassRank::factory()->create(['character_id' => $character->id, 'game_class_id' => $gameClass->id, 'level' => 2]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);

        $this->assertArrayHasKey('class_ranks_offered', $data);
        $this->assertArrayHasKey('class_rank_specialties', $data);
        $this->assertArrayHasKey('class_specialties', $data['class_rank_specialties']);
        $this->assertArrayHasKey('specials_equipped', $data['class_rank_specialties']);
        $this->assertArrayHasKey('class_ranks', $data['class_rank_specialties']);
        $this->assertArrayHasKey('other_class_specials', $data['class_rank_specialties']);
    }

    public function testClassRanksOfferedReturnsCorrectOfferedSkillsForEachOfMultipleClasses(): void
    {
        $user = User::factory()->create();
        $firstClass = GameClass::factory()->create();
        $secondClass = GameClass::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'game_class_id' => $firstClass->id]);
        CharacterClassRank::factory()->create(['character_id' => $character->id, 'game_class_id' => $firstClass->id, 'level' => 2]);
        CharacterClassRank::factory()->create(['character_id' => $character->id, 'game_class_id' => $secondClass->id, 'level' => 2]);
        $firstClassSkill = GameSkill::factory()->create(['game_class_id' => $firstClass->id, 'name' => 'First Class Skill']);
        $secondClassSkill = GameSkill::factory()->create(['game_class_id' => $secondClass->id, 'name' => 'Second Class Skill']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->classRanksOffered($character);

        $firstOffered = collect($data)->firstWhere('class_id', $firstClass->id);
        $secondOffered = collect($data)->firstWhere('class_id', $secondClass->id);

        $this->assertSame('First Class Skill', $firstOffered['offered_game_skills'][0]['name']);
        $this->assertSame('Second Class Skill', $secondOffered['offered_game_skills'][0]['name']);
        $this->assertCount(1, $firstOffered['offered_game_skills']);
        $this->assertCount(1, $secondOffered['offered_game_skills']);
    }

    public function testQuestDetailPayloadIncludesRequiredQuestChainAfterRemovingPerQuestLoadRelations(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id]);
        $gameMap = GameMap::factory()->create();
        $npc = Npc::factory()->create(['game_map_id' => $gameMap->id]);
        $requiredQuest = Quest::factory()->create(['npc_id' => $npc->id, 'name' => 'Required First']);
        $quest = Quest::factory()->create(['npc_id' => $npc->id, 'required_quest_id' => $requiredQuest->id, 'name' => 'Public Quest']);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $owner);
        $details = $data['completed_quests'][0]['details'];

        $this->assertSame('Required First', $details['required_quest']['name']);
    }

    public function testQuestSummaryChartProducesCumulativeAndAverageValuesForDeterministicFixture(): void
    {
        $owner = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $owner->id, 'name' => 'Inspected', 'created_at' => now()->subDays(10)]);
        $otherOwnerOne = User::factory()->create();
        $otherOwnerTwo = User::factory()->create();
        $otherCharacterOne = Character::factory()->create(['user_id' => $otherOwnerOne->id, 'name' => 'Other One', 'created_at' => now()->subDays(10)]);
        $otherCharacterTwo = Character::factory()->create(['user_id' => $otherOwnerTwo->id, 'name' => 'Other Two', 'created_at' => now()->subDays(10)]);

        $npc = Npc::factory()->create();
        $questOne = Quest::factory()->create(['npc_id' => $npc->id]);
        $questTwo = Quest::factory()->create(['npc_id' => $npc->id]);

        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questOne->id, 'created_at' => now()->subDays(9)]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $questTwo->id, 'created_at' => now()->subDays(8)]);
        QuestsCompleted::factory()->create(['character_id' => $otherCharacterOne->id, 'quest_id' => $questOne->id, 'created_at' => now()->subDays(9)]);
        QuestsCompleted::factory()->create(['character_id' => $otherCharacterTwo->id, 'quest_id' => $questOne->id, 'created_at' => now()->subDays(9)]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->quests($character, $owner);
        $summaryChart = $data['summary_chart'];

        $inspectedSeries = collect($summaryChart['series'])->firstWhere('label', 'Inspected Completed Quests');
        $othersSeries = collect($summaryChart['series'])->firstWhere('label', 'Average Completed Quests For Everyone Else');

        $this->assertSame(2, end($inspectedSeries['points'])['value']);
        $this->assertSame(1.0, end($othersSeries['points'])['value']);
    }

    public function testFullProfileStillReturnsAllExistingSections(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->fullProfile($character, $user);

        $this->assertArrayHasKey('overview', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('additional_stats', $data);
        $this->assertArrayHasKey('equipment', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('crafting_skills', $data);
        $this->assertArrayHasKey('kingdom_passives', $data);
        $this->assertArrayHasKey('class_ranks', $data);
        $this->assertArrayHasKey('class_ranks_offered', $data);
        $this->assertArrayHasKey('factions', $data);
        $this->assertArrayHasKey('reincarnation', $data);
        $this->assertArrayHasKey('activity', $data);
        $this->assertArrayHasKey('quests', $data);
        $this->assertArrayHasKey('kingdoms', $data);
        $this->assertArrayHasKey('analytics', $data);
    }
}
