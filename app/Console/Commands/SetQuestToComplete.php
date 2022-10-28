<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use Illuminate\Console\Command;

class SetQuestToComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-quest:complete {characterName} {questName=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will set the quest as complete if they have the reward item';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $character = Character::where('name', $this->argument('characterName'))->first();

        if (is_null($character)) {
            $this->error('Character not found.');

            return;
        }

        if ($this->argument('questName') === 'all') {
            $quests = Quest::all();

            foreach ($quests as $quest) {
                $this->completeQuest($character, $quest);
            }
        } else {
            $quest = Quest::where('name', $this->argument('questName'))->first();

            if (is_null($quest)) {
                $this->error('Quest not found');

                return;
            }

            $this->completeQuest($character, $quest);
        }

    }

    protected function completeQuest(Character $character, Quest $quest) {
        $hasItem = $character->inventory->slots->filter(function($slot) use($quest) {
            return $slot->item_id === $quest->reward_item;
        })->isNotEmpty();

        $hasQuestCompletedLog = $character->questsCompleted->filter(function($completedQuest) use($quest) {
            return $completedQuest->quest_id === $quest->id;
        })->isEmpty();

        if ($hasItem && $hasQuestCompletedLog) {
            $character->questsCompleted()->create([
                'character_id' => $character->id,
                'quest_id'     => $quest->id,
            ]);

            $this->line('Added quest log for character: ' . $character->name . ' For quest: ' . $quest->name);

            return;
        }

        $this->line('No quest log was created for: ' . $character->name);
    }
}
