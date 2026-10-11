<?php

namespace Tests\Console\GuideQuests;

use App\Game\GuideQuests\Events\ShowGuideQuestCompletedToast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateUserSession;

class CheckForCompleteGuideQuestsTest extends TestCase
{
    use CreateGuideQuest, CreateUserSession, RefreshDatabase;

    public function test_command_checks_later_users_after_finding_a_ready_guide_quest(): void
    {
        $firstCharacter = (new CharacterFactory)
            ->setAttributesOnUserForCreation(['guide_enabled' => true])
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $secondCharacter = (new CharacterFactory)
            ->setAttributesOnUserForCreation(['guide_enabled' => true])
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->createUserSession($firstCharacter->user);
        $this->createUserSession($secondCharacter->user);
        $this->createGuideQuest([
            'name' => 'Ready Guide Quest',
            'required_level' => 1,
        ]);

        Event::fake([ShowGuideQuestCompletedToast::class]);

        $this->artisan('check:for-complete-guide-quests')->assertSuccessful();

        Event::assertDispatched(
            ShowGuideQuestCompletedToast::class,
            fn (ShowGuideQuestCompletedToast $event): bool => $event->showQuestCompleted
                && $event->broadcastOn()->name === 'private-guide-quest-completed-toast-'.$firstCharacter->user_id,
        );
        Event::assertDispatched(
            ShowGuideQuestCompletedToast::class,
            fn (ShowGuideQuestCompletedToast $event): bool => $event->showQuestCompleted
                && $event->broadcastOn()->name === 'private-guide-quest-completed-toast-'.$secondCharacter->user_id,
        );
    }
}
