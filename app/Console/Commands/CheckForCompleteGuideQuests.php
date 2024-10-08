<?php

namespace App\Console\Commands;

use App\Flare\Models\GuideQuest;
use App\Flare\Models\User;
use App\Game\GuideQuests\Events\ShowGuideQuestCompletedToast;
use App\Game\GuideQuests\Services\GuideQuestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckForCompleteGuideQuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:for-complete-guide-quests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for completed guide quests while the user is logged in.';

    /**
     * Execute the console command.
     */
    public function handle(GuideQuestService $guideQuestService)
    {
        $userIds = DB::table('sessions')->pluck('user_id');

        User::whereIn('id', $userIds)->where('guide_enabled', true)->chunkById(50, function ($users) use ($guideQuestService) {
            foreach ($users as $user) {

                $character = $user->character;

                $data = $guideQuestService->fetchQuestForCharacter($character);

                foreach ($data['quests'] as $quest) {
                    if ($this->canHandIn($quest, $data['can_hand_in'])) {
                        event(new ShowGuideQuestCompletedToast($user, true));

                        return;
                    }
                }
            }
        });
    }

    protected function canHandIn(GuideQuest $guideQuest, array $canHandIn) {
        foreach ($canHandIn as $handIn) {
            if ($handIn['quest_id'] === $guideQuest['id']) {
                return $handIn['can_hand_in'];
            }
        }

        return false;
    }
}
