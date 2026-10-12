<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\GuideQuests\Services\Requirements\GlobalEventGuideQuestRequirementService;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateScheduledEvent;

class GlobalEventGuideQuestRequirementServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, CreateGuideQuest, CreateScheduledEvent, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GlobalEventGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new GlobalEventGuideQuestRequirementService(
            new GlobalEventGoalEligibilityService,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestRequirementsService = null;
    }

    public function test_player_has_global_kill_amount()
    {
        $character = $this->character->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapName::ICE_PLANE->value,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
            'unique_type' => RandomAffixTier::LEGENDARY->value,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 100,
            'current_crafts' => null,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'kills' => 100,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_participation' => 10,
            'only_during_event' => EventType::WINTER_EVENT,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_event_goal_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_kill_amount_when_no_event_running()
    {
        $character = $this->character->getCharacter();

        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_event_goal_participation', $finishedRequirements);
    }

    public function test_player_has_global_event_craft_amount()
    {
        $character = $this->character->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
            'unique_type' => RandomAffixTier::LEGENDARY->value,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 100,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_craft_amount_when_no_craft_row_exists()
    {
        $character = $this->character->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_craft_amount_when_crafts_are_below_requirement()
    {
        $character = $this->character->getCharacter();
        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
            'unique_type' => RandomAffixTier::LEGENDARY->value,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 5,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function test_player_has_global_event_enchant_amount()
    {
        $character = $this->character->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
            'unique_type' => RandomAffixTier::LEGENDARY->value,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'enchants' => 100,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_enchant_amount_when_no_enchant_row_exists()
    {
        $character = $this->character->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_enchant_amount_when_enchants_are_below_requirement()
    {
        $character = $this->character->getCharacter();
        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
            'unique_type' => RandomAffixTier::LEGENDARY->value,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'enchants' => 5,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }
}
