<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\QuestsCompleted;
use App\Game\GuideQuests\Services\GuideQuestService;
use Illuminate\Console\Command;

class CompleteGuideQuestForCharacter extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complete:guide-quest-for-character {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completes a single Guide Quest For the Character';

    /**
     * Execute the console command.
     */
    public function handle(GuideQuestService $guideQuestService) {
        $character = Character::where('name', $this->argument('characterName'))->first();

        if (is_null($character)) {
            return $this->error('No character found.');
        }

        $guideQuest = $this->getNextQuest($guideQuestService, $character);

        if (is_null($guideQuest)) {
            return $this->line('No more guide quests for this character');
        }

        QuestsCompleted::create([
            'character_id' => $character->id,
            'guide_quest_id' => $guideQuest->id,
        ]);

        $this->line('Guide quest has been advanced for this character. Refresh in game.');
    }

    protected function getNextQuest(GuideQuestService $guideQuestService, Character $character): GuideQuest | null {
        $data = $guideQuestService->fetchQuestForCharacter($character);

        if (!is_null($data)) {

            $quest = $data['quest'];

            return $quest;
        }

        return null;
    }
}
